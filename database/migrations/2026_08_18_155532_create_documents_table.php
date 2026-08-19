<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
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
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('converted_from_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('type');
            $table->string('status')->default(DocumentStatus::Draft->value);
            $table->string('number');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->text('client_address')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3);
            $table->decimal('tax_rate', total: 5, places: 2)->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'number']);
            $table->index(['user_id', 'type', 'status']);
            $table->index(['user_id', 'type', 'issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
