<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('external_id')->unique()->comment('UUID Keycloak');
            
            // Informations personnelles
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('email')->unique();
            $table->string('telephone')->unique();
            
            // Type de client
            $table->enum('type_client', ['bancaire', 'non_bancaire']);
            
            // Pour clients bancaires
            $table->string('numero_compte_bancaire')->nullable();
            $table->string('telephone_cbs')->nullable()->comment('Tel dans le CBS');
            $table->decimal('solde_min_declare', 15, 2)->nullable();
            $table->decimal('solde_max_declare', 15, 2)->nullable();
            
            // Documents d'identité
            $table->enum('type_piece', ['cni', 'passeport', 'recu_carte_nationale'])->nullable();
            $table->string('numero_piece_identite')->nullable();
            $table->string('niu')->nullable()->comment('Numéro d\'Identification Unique');
            $table->date('date_expiration_piece')->nullable();
            
            // Parrainage
            $table->string('recommande_par_tel')->nullable();
            
            // Statuts
            $table->enum('statut', ['actif', 'inactif', 'suspendu', 'en_attente_validation'])->default('en_attente_validation');
            $table->enum('statut_kyc', ['en_attente', 'en_cours', 'valide', 'rejete'])->default('en_attente');
            
            $table->timestamps();
            
            // Index
            $table->index(['external_id', 'statut']);
            $table->index(['type_client', 'statut_kyc']);
            $table->index(['telephone']);
            $table->index(['numero_compte_bancaire']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};