<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dirigeants_services', function (Blueprint $table) {
            if (!Schema::hasColumn('dirigeants_services', 'gsm')) {
                $table->string('gsm')->nullable()->after('carte_identite');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dirigeants_services', function (Blueprint $table) {
            if (Schema::hasColumn('dirigeants_services', 'gsm')) {
                $table->dropColumn('gsm');
            }
        });
    }
};
