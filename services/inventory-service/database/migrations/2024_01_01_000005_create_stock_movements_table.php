<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36)->index();
            $table->uuid('product_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->string('type', 30)->index()->comment('in|out|transfer|adjustment|reservation|release');
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 36)->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('performed_by', 36)->index();
            $table->timestamp('created_at')->useCurrent();

            // No updated_at – movements are immutable
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
