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
        Schema::create('dirigeants_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('demande_id');
            $table->foreign('demande_id')->references('id')->on('demandes')->onDelete('cascade');
            $table->string("surname");
            $table->string("givenName");
            // $table->string("GSM")->nullable();
            // $table->uuid("veriff_session")->nullable();
            $table->boolean("is_modified")->default(value: false);
            $table->enum('status', ['pending', 'accepted', 'refused'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dirigeants_services');
    }
};
