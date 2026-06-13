<?php

namespace App\Models;

use App\SubscriptionStatus;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property int $customer_address_id
 * @property SubscriptionStatus $status
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $paused_until
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'customer_id',
    'customer_address_id',
    'status',
    'start_date',
    'end_date',
    'paused_until',
    'notes',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'paused_until' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<CustomerAddress, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    /**
     * @return HasMany<SubscriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    /**
     * @return HasMany<SubscriptionSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(SubscriptionSchedule::class);
    }

    /**
     * @return HasMany<SubscriptionPause, $this>
     */
    public function pauses(): HasMany
    {
        return $this->hasMany(SubscriptionPause::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function hasActiveOrUpcomingPause(): bool
    {
        return $this->pauses()
            ->where('end_date', '>=', today()->toDateString())
            ->exists();
    }

    public function isPausable(): bool
    {
        return $this->status === SubscriptionStatus::Active
            && ! $this->hasActiveOrUpcomingPause();
    }

    public function isResumable(): bool
    {
        return $this->status !== SubscriptionStatus::Cancelled
            && ($this->status === SubscriptionStatus::Paused || $this->hasActiveOrUpcomingPause());
    }

    public function isPausedOnDate(Carbon|CarbonInterface $date): bool
    {
        return $this->pauses()
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
    }

    public function hasScheduleOnDay(int $dayOfWeek): bool
    {
        return $this->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->exists();
    }
}
