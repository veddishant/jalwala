<?php

namespace App\Models;

use App\DepositTransactionType;
use App\Traits\BelongsToTenant;
use Database\Factories\DepositTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_deposit_id
 * @property DepositTransactionType $type
 * @property string $amount
 * @property string $balance_after
 * @property int $jar_count
 * @property int|null $product_id
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'customer_deposit_id',
    'type',
    'amount',
    'balance_after',
    'jar_count',
    'product_id',
    'reference_type',
    'reference_id',
    'description',
    'created_by',
])]
class DepositTransaction extends Model
{
    /** @use HasFactory<DepositTransactionFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DepositTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'jar_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CustomerDeposit, $this>
     */
    public function customerDeposit(): BelongsTo
    {
        return $this->belongsTo(CustomerDeposit::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
