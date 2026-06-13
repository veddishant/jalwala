<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\SubscriptionPauseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $subscription_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string|null $reason
 * @property int|null $created_by
 */
#[Fillable([
    'subscription_id',
    'start_date',
    'end_date',
    'reason',
    'created_by',
])]
class SubscriptionPause extends Model
{
    /** @use HasFactory<SubscriptionPauseFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
