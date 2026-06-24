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
        Schema::table('demandes', function (Blueprint $table) {
            if (!Schema::hasColumn('demandes', 'pdf_fields')) {
                $table->json('pdf_fields')->nullable()->after('extrait_pub_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            if (Schema::hasColumn('demandes', 'pdf_fields')) {
                $table->dropColumn('pdf_fields');
            }
        });
    }
};
