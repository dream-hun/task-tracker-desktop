<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', total: 10, places: 2)->default(1);
            $table->unsignedBigInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
