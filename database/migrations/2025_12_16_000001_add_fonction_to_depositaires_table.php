<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depositaires', function (Blueprint $table) {
            $table->string('fonction')->nullable()->after('gsm');
        });
    }

    public function down(): void
    {
        Schema::table('depositaires', function (Blueprint $table) {
            $table->dropColumn('fonction');
        });
    }
};
