<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\WalletFactory;
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
 * @property string $balance
 * @property string|null $low_balance_threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'customer_id',
    'balance',
    'low_balance_threshold',
])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'low_balance_threshold' => 'decimal:2',
        ];
    }

    public function isBelowThreshold(): bool
    {
        if ($this->low_balance_threshold === null) {
            return false;
        }

        return bccomp($this->balance, $this->low_balance_threshold, 2) < 0;
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderByDesc('created_at');
    }
}
