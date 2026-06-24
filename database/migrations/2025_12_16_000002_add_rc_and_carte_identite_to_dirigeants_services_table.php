<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dirigeants_services', function (Blueprint $table) {
            if (!Schema::hasColumn('dirigeants_services', 'rc')) {
                $table->string('rc')->nullable()->after('function');
            }
            if (!Schema::hasColumn('dirigeants_services', 'carte_identite')) {
                $table->string('carte_identite')->nullable()->after('rc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dirigeants_services', function (Blueprint $table) {
            if (Schema::hasColumn('dirigeants_services', 'carte_identite')) {
                $table->dropColumn('carte_identite');
            }
            if (Schema::hasColumn('dirigeants_services', 'rc')) {
                $table->dropColumn('rc');
            }
        });
    }
};
