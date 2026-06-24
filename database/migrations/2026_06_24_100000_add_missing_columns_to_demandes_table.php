<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            if (!Schema::hasColumn('demandes', 'depositaire')) {
                $table->string('depositaire')->nullable();
            }
            if (!Schema::hasColumn('demandes', 'GSM')) {
                $table->string('GSM')->nullable();
            }
            if (!Schema::hasColumn('demandes', 'veriff_url')) {
                $table->text('veriff_url')->nullable();
            }
            if (!Schema::hasColumn('demandes', 'veriff_session')) {
                $table->string('veriff_session')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            foreach (['depositaire', 'GSM', 'veriff_url', 'veriff_session'] as $col) {
                if (Schema::hasColumn('demandes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
