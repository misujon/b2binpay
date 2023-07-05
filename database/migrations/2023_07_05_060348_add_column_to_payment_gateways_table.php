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
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('username')->nullable()->comment('Gateway Access username');
            $table->longText('password')->nullable()->comment('Gateway Access password');
            $table->integer('wallet')->nullable()->comment('Gateway Access wallet ID');
            $table->longText('refresh_token')->nullable()->comment('Gateway tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn('username');
            $table->dropColumn('password');
            $table->dropColumn('wallet');
            $table->dropColumn('refresh_token');
        });
    }
};
