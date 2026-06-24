<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('depositaires')) {
            Schema::create('depositaires', function (Blueprint $table) {
                $table->id();
                // demandes.id is a UUID (char 36) — use foreignUuid so the FK types match
                $table->foreignUuid('demande_id')->constrained('demandes')->onDelete('cascade');
                // Informations personnelles
                $table->string('nom');
                $table->string('prenom');
                $table->date('date_naissance')->nullable();
                $table->string('lieu_naissance')->nullable();

                // Identification
                $table->string('numero_registre_national')->nullable();

                // Adresse
                $table->string('domicile')->nullable();

                // Carte d'identité (fichier)
                $table->string('carte_identite')->nullable();

                // Informations membre
                $table->string('numero_membre')->nullable();

                // Cabinet
                $table->string('nom_cabinet')->nullable();

                // Contact
                $table->string('gsm')->nullable();
                $table->string('type');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('depositaires');
    }
};
