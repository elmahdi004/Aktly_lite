<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->integer('max_users')->nullable()->comment('null = unlimited');
            $table->integer('max_dossiers')->nullable()->comment('null = unlimited');
            $table->string('stripe_coupon_id')->nullable()->comment('Stripe coupon for discount');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
