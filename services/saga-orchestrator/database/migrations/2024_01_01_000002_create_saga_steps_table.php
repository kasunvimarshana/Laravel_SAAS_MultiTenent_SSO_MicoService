<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('saga_transaction_id')->index();
            $table->string('name', 100);
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('order')->default(0);
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('saga_transaction_id')
                ->references('id')
                ->on('saga_transactions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_steps');
    }
};
