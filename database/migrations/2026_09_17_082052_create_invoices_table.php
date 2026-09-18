<?php

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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->enum('status', ['DRAFT', 'SENT', 'PARTIALLY_PAID', 'PAID', 'VOID'])->default('DRAFT');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->enum('discount_type', ['NONE', 'PERCENT', 'AMOUNT'])->default('NONE');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
