<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function uploadDocument(User $user, UploadedFile $file, string $type): UserDocument
    {
        $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = "documents/{$user->id}/{$type}/";
        
        $storedPath = Storage::putFileAs($path, $file, $fileName);

        return $user->documents()->create([
            'type_document' => $type,
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_fichier' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'taille_fichier' => $file->getSize(),
        ]);
    }
}