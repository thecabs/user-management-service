<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->enum('type_document', [
                'piece_face_avant',
                'piece_face_arriere', 
                'selfie',
                'autre'
            ]);
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type');
            $table->integer('taille_fichier');
            $table->enum('statut_verification', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->text('commentaire_rejet')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'type_document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};