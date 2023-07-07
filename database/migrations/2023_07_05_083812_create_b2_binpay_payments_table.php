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
        Schema::create('b2_binpay_payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id');
            $table->string('name');
            $table->string('label');
            $table->string('address');
            $table->string('destination');
            $table->string('tracking_id');
            $table->string('reference')->nullable();

            $table->string('target_amount_requested', 50);
            $table->string('target_paid', 50);
            $table->string('target_commission', 50);
            $table->string('source_amount_requested', 50);
            $table->string('currency');
            $table->string('wallet');
            $table->enum('status', ['INVOICE', 'PAID', 'CANCELED', 'UNRESOLVED'])->default('INVOICE');

            $table->dateTime('expired_at');
            $table->integer('confirmations_needed');
            $table->string('callback_url');
            $table->string('payment_page');
            $table->integer('time_limit');
            $table->string('message')->nullable();
            $table->longText('response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2_binpay_payments');
    }
};
