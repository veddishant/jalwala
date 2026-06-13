<?php

namespace App\Models;

use App\InventoryLocationType;
use App\Traits\BelongsToTenant;
use Database\Factories\InventoryLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property InventoryLocationType $locatable_type
 * @property int $locatable_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'locatable_type',
    'locatable_id',
    'name',
])]
class InventoryLocation extends Model
{
    /** @use HasFactory<InventoryLocationFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locatable_type' => InventoryLocationType::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'locatable_id');
    }

    /**
     * @return HasMany<InventoryBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function isWarehouse(): bool
    {
        return $this->locatable_type === InventoryLocationType::TenantWarehouse;
    }

    public function isCustomerLocation(): bool
    {
        return $this->locatable_type === InventoryLocationType::Customer;
    }
}
