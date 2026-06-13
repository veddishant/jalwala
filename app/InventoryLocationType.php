<?php

namespace App;

enum InventoryLocationType: string
{
    case TenantWarehouse = 'tenant_warehouse';
    case Customer = 'customer';
}
