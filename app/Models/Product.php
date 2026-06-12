<?php

namespace App\Models;

use App\ProductStatus;
use App\ProductType;
use App\Traits\BelongsToTenant;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $sku
 * @property ProductType $type
 * @property string|null $capacity_liters
 * @property string $unit_price
 * @property string $deposit_amount
 * @property bool $is_returnable
 * @property ProductStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'sku',
    'type',
    'capacity_liters',
    'unit_price',
    'deposit_amount',
    'is_returnable',
    'status',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'capacity_liters' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'is_returnable' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }
}
