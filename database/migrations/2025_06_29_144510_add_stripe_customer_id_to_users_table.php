<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // database/migrations/…_add_stripe_customer_id_to_users_table.php
public function up(): void
{
    Schema::table('users', function ($table) {
        $table->string('stripe_customer_id')->nullable()->after('email');
    });
}
public function down(): void
{
    Schema::table('users', function ($table) {
        $table->dropColumn('stripe_customer_id');
    });
}

};
