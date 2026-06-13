<?php

namespace App;

enum ReportType: string
{
    case Sales = 'sales';
    case Consumption = 'consumption';
    case Wallet = 'wallet';
    case Deposits = 'deposits';
    case Outstanding = 'outstanding';
    case AgentPerformance = 'agent-performance';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Consumption => 'Consumption',
            self::Wallet => 'Wallet',
            self::Deposits => 'Deposits',
            self::Outstanding => 'Outstanding Balances',
            self::AgentPerformance => 'Agent Performance',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sales => 'Revenue, order counts, and averages by period and product.',
            self::Consumption => 'Units and liters delivered per customer and product.',
            self::Wallet => 'Top-ups, debits, adjustments, and negative balances.',
            self::Deposits => 'Deposits held, collected, and refunded by product.',
            self::Outstanding => 'Customers with negative wallet balances and aging.',
            self::AgentPerformance => 'Deliveries completed, failed, and agent leaderboard.',
        };
    }

    public function permission(): string
    {
        return 'reports.'.$this->value;
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
