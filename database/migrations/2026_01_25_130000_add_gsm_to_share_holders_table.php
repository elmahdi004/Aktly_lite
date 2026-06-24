<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_holders', function (Blueprint $table) {
            if (!Schema::hasColumn('share_holders', 'gsm')) {
                $table->string('gsm')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('share_holders', function (Blueprint $table) {
            if (Schema::hasColumn('share_holders', 'gsm')) {
                $table->dropColumn('gsm');
            }
        });
    }
};
