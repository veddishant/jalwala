<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for tenant-isolated domain records introduced in later phases.
 */
abstract class TenantScopedRecord extends Model
{
    use BelongsToTenant;
}
