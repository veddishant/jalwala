<?php

namespace App\Services;

use App\ReportType;
use App\Repositories\ReportRepository;
use Closure;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    private const CACHE_TTL_SECONDS = 900;

    public function __construct(
        private ReportRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(ReportType $type, int $tenantId, array $filters): array
    {
        return $this->remember($tenantId, $type, $filters, function () use ($type, $tenantId, $filters): array {
            return match ($type) {
                ReportType::Sales => $this->repository->sales($tenantId, $filters),
                ReportType::Consumption => $this->repository->consumption($tenantId, $filters),
                ReportType::Wallet => $this->repository->wallet($tenantId, $filters),
                ReportType::Deposits => $this->repository->deposits($tenantId, $filters),
                ReportType::Outstanding => $this->repository->outstanding($tenantId),
                ReportType::AgentPerformance => $this->repository->agentPerformance($tenantId, $filters),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(ReportType $type, int $tenantId, array $filters): StreamedResponse
    {
        $data = $this->generate($type, $tenantId, $filters);
        $filename = $type->value.'-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($type, $data): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            foreach ($this->csvRows($type, $data) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function remember(int $tenantId, ReportType $type, array $filters, Closure $callback): array
    {
        $cacheKey = $this->cacheKey($tenantId, $type, $filters);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, $callback);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function clearCache(int $tenantId, ReportType $type, array $filters): void
    {
        Cache::forget($this->cacheKey($tenantId, $type, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function cacheKey(int $tenantId, ReportType $type, array $filters): string
    {
        ksort($filters);

        return sprintf(
            '%d:%s:%s',
            $tenantId,
            $type->value,
            hash('xxh128', json_encode($filters, JSON_THROW_ON_ERROR)),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|int|float|null>>
     */
    private function csvRows(ReportType $type, array $data): array
    {
        return match ($type) {
            ReportType::Sales => $this->csvFromRows(
                ['Customer', 'Code', 'Source', 'Date', 'Total', 'Status'],
                collect($data['rows'] ?? [])->map(fn (array $row): array => [
                    $row['customer_name'] ?? '',
                    $row['customer_code'] ?? '',
                    $row['source_label'] ?? '',
                    $row['scheduled_date'] ?? '',
                    $row['total'] ?? '',
                    $row['status'] ?? '',
                ])->all(),
            ),
            ReportType::Consumption => $this->csvFromRows(
                ['Customer', 'Code', 'Product', 'Units', 'Liters'],
                collect($data['rows'] ?? [])->map(fn (array $row): array => [
                    $row['customer_name'] ?? '',
                    $row['customer_code'] ?? '',
                    $row['product_name'] ?? '',
                    $row['units'] ?? '',
                    $row['liters'] ?? '',
                ])->all(),
            ),
            ReportType::Wallet => $this->csvFromRows(
                ['Customer', 'Code', 'Type', 'Category', 'Amount', 'Balance After', 'Date'],
                collect($data['rows'] ?? [])->map(fn (array $row): array => [
                    $row['customer_name'] ?? '',
                    $row['customer_code'] ?? '',
                    $row['type'] ?? '',
                    $row['category'] ?? '',
                    $row['amount'] ?? '',
                    $row['balance_after'] ?? '',
                    $row['created_at'] ?? '',
                ])->all(),
            ),
            ReportType::Deposits => $this->csvFromRows(
                ['Customer', 'Code', 'Balance Held'],
                collect($data['rows'] ?? [])->map(fn (array $row): array => [
                    $row['customer_name'] ?? '',
                    $row['customer_code'] ?? '',
                    $row['balance'] ?? '',
                ])->all(),
            ),
            ReportType::Outstanding => $this->csvFromRows(
                ['Customer', 'Code', 'Phone', 'Amount Owed', 'Days Negative', 'Aging'],
                collect($data['rows'] ?? [])->map(fn (array $row): array => [
                    $row['customer_name'] ?? '',
                    $row['customer_code'] ?? '',
                    $row['phone'] ?? '',
                    $row['amount_owed'] ?? '',
                    $row['days_negative'] ?? '',
                    $row['aging_bucket'] ?? '',
                ])->all(),
            ),
            ReportType::AgentPerformance => $this->csvFromRows(
                ['Agent', 'Delivered', 'Failed', 'Avg Minutes', 'Orders/Day'],
                collect($data['rows'] ?? [])->map(fn (array $row): array => [
                    $row['agent_name'] ?? '',
                    $row['delivered_count'] ?? '',
                    $row['failed_count'] ?? '',
                    $row['avg_delivery_minutes'] ?? '',
                    $row['orders_per_day'] ?? '',
                ])->all(),
            ),
        };
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @return list<list<string|int|float|null>>
     */
    private function csvFromRows(array $headers, array $rows): array
    {
        return [$headers, ...$rows];
    }
}
