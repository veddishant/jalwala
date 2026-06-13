<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('filled_quantity')->default(0);
            $table->unsignedInteger('empty_quantity')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'inventory_location_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
