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
        if (!Schema::hasColumn('demandes', 'extrait_pub_text')) {
            Schema::table('demandes', function (Blueprint $table) {
                $table->text('extrait_pub_text')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('demandes', 'extrait_pub_text')) {
            Schema::table('demandes', function (Blueprint $table) {
                $table->dropColumn('extrait_pub_text');
            });
        }
    }
};
