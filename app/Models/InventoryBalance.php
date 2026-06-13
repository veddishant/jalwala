<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\InventoryBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $inventory_location_id
 * @property int $product_id
 * @property int $filled_quantity
 * @property int $empty_quantity
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'inventory_location_id',
    'product_id',
    'filled_quantity',
    'empty_quantity',
])]
class InventoryBalance extends Model
{
    /** @use HasFactory<InventoryBalanceFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filled_quantity' => 'integer',
            'empty_quantity' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
