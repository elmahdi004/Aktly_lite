<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // 'rue' => $request->rue,
    // 'numero' => $request->numero,
    // 'box' => $request->box,
    // 'zip_code' => $request->zip_code,
    // 'localite' => $request->localite,
    // 'pays' => $request->pays,
    public function up(): void
    {
        Schema::table('address_service', function (Blueprint $table) {
            $table->string('rue')->nullable();
            $table->string('numero')->nullable();
            $table->string('box')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('localite')->nullable();
            $table->string('pays')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('address_service', function (Blueprint $table) {
            //
        });
    }
};
