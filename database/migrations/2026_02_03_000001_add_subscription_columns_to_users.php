<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stripe subscription
            $table->string('stripe_subscription_id')->nullable()->unique()->after('stripe_customer_id');
            $table->string('subscription_plan')->nullable()->comment('starter, pro, business')->after('stripe_subscription_id');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_plan');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_started_at');
            $table->string('subscription_status')->nullable()->comment('active, past_due, canceled')->after('subscription_ends_at');
            
            // Dossier counting
            $table->unsignedInteger('cumulative_dossier_count')->default(0)->after('subscription_status');
            
            // Coupon
            $table->string('applied_coupon_code')->nullable()->after('cumulative_dossier_count');
            $table->timestamp('coupon_applied_at')->nullable()->after('applied_coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_subscription_id',
                'subscription_plan',
                'subscription_started_at',
                'subscription_ends_at',
                'subscription_status',
                'cumulative_dossier_count',
                'applied_coupon_code',
                'coupon_applied_at',
            ]);
        });
    }
};
