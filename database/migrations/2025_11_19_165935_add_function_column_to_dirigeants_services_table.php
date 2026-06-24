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
        Schema::table('dirigeants_services', function (Blueprint $table) {
            if (!Schema::hasColumn('dirigeants_services', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false);
            }
            if (!Schema::hasColumn('dirigeants_services', 'function')) {
                $table->string('function')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dirigeants_services', function (Blueprint $table) {
            if (Schema::hasColumn('dirigeants_services', 'function')) {
                $table->dropColumn('function');
            }
            if (Schema::hasColumn('dirigeants_services', 'is_deleted')) {
                $table->dropColumn('is_deleted');
            }
        });
    }
};
