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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(
                table: 'users',
                indexName: 'transactions_user_id'
            )->onDelete('cascade');
            $table->foreignId('validated_by')->nullable()->constrained(
                table: 'users',
                indexName: 'transactions_admin_id'
            )->onDelete('cascade');
            $table->string('delivery');
            $table->string('notes')->nullable();
            $table->string('payment_proof');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
