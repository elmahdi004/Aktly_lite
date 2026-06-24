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
        Schema::create('demandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->references('id')->onDelete('cascade');
            $table->string('enterprise_number');
            $table->enum('langue_entreprise', ['fr', 'nl']);
            $table->json('bce_data');
            $table->boolean('in_enterprise')->default(false);
            $table->date('date_changement')->nullable();
            $table->date('date_assemblee')->nullable();
            // $table->uuid('veriff_session')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('address_service')->default(false);
            $table->boolean('dirigeants_service')->default(false);
            $table->boolean('cession_parts_service')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
