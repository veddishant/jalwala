<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\WalletTransactionCategory;
use App\WalletTransactionType;
use Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $wallet_id
 * @property WalletTransactionType $type
 * @property WalletTransactionCategory $category
 * @property string $amount
 * @property string $balance_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $idempotency_key
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'wallet_id',
    'type',
    'category',
    'amount',
    'balance_after',
    'reference_type',
    'reference_id',
    'idempotency_key',
    'description',
    'created_by',
])]
class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'category' => WalletTransactionCategory::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
