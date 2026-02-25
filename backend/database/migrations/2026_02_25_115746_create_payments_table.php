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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debtor_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
                //debtor id is from user id, the user paying back
                $table->foreignId('creditor_id')
                      ->constrained('users')
                      ->cascadeOnDelete();
                //creditor tp user-> the payment is received by this person
                $table->decimal('amount', 10, 2);
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
