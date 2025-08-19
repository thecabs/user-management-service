<?php

namespace App\Services;

use App\Models\UserDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function uploadDocument(UploadedFile $file, string $userId, string $type): UserDocument
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "documents/{$userId}/{$type}/";
        $storedPath = Storage::putFileAs($path, $file, $fileName);

        return UserDocument::create([
            'user_id' => $userId,
            'type_document' => $type,
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_fichier' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'taille_fichier' => $file->getSize(),
        ]);
    }

    public function verifyDocument(string $documentId, bool $isValid, ?string $comment = null): UserDocument
    {
        $document = UserDocument::findOrFail($documentId);
        $document->update([
            'statut_verification' => $isValid ? 'valide' : 'rejete',
            'commentaire_rejet' => !$isValid ? $comment : null
        ]);
        
        return $document;
    }
}