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
        Schema::create('expense_participants_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->foreignId('creditor_id')->constrained('users');
            $table->foreignId('debtor_id')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['expense_id', 'debtor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_participants_splits');
    }
};
