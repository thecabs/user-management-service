<?php

namespace App\Services;

use App\Enums\KycStatus;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserAddress;
use App\Models\UserContact;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KycService
{
    public function createBankingClient(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'external_id' => request()->attributes->get('external_id'),
                'type_client' => 'bancaire',
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'date_naissance' => $data['date_naissance'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'numero_compte_bancaire' => $data['numero_compte_bancaire'],
                'telephone_cbs' => $data['telephone_cbs'],
                'solde_min_declare' => $data['solde_min_declare'],
                'solde_max_declare' => $data['solde_max_declare'],
                'recommande_par_tel' => $data['recommande_par_tel'] ?? null,
                'statut_kyc' => KycStatus::VALIDE,
            ]);

            $user->addresses()->create([
                'region' => $data['region'],
                'ville' => $data['ville'],
                'quartier' => $data['quartier'],
                'adresse_principale' => true,
            ]);

            $user->contacts()->create([
                'telephone_recuperation' => $data['telephone_recuperation'] ?? null,
                'email_recuperation' => $data['email_recuperation'] ?? null,
            ]);

            return $user;
        });
    }

    public function createNonBankingClient(array $data, array $files): User
    {
        return DB::transaction(function () use ($data, $files) {
            $user = User::create([
                'external_id' => request()->attributes->get('external_id'),
                'type_client' => 'non_bancaire',
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'date_naissance' => $data['date_naissance'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'type_piece' => $data['type_piece'],
                'numero_piece_identite' => $data['numero_piece_identite'],
                'niu' => $data['niu'] ?? null,
                'date_expiration_piece' => $data['date_expiration_piece'],
                'recommande_par_tel' => $data['recommande_par_tel'] ?? null,
                'statut_kyc' => KycStatus::EN_ATTENTE,
            ]);

            $this->storeUserDocuments($user, $files);

            $user->addresses()->create([
                'region' => $data['region'],
                'ville' => $data['ville'],
                'quartier' => $data['quartier'],
                'adresse_principale' => true,
            ]);

            $user->contacts()->create([
                'telephone_recuperation' => $data['telephone_recuperation'] ?? null,
                'email_recuperation' => $data['email_recuperation'] ?? null,
            ]);

            return $user;
        });
    }

    private function storeUserDocuments(User $user, array $files): void
    {
        $documentsMapping = [
            'piece_face_avant' => 'piece_face_avant',
            'piece_face_arriere' => 'piece_face_arriere',
            'selfie' => 'selfie',
        ];

        foreach ($documentsMapping as $fileKey => $documentType) {
            if (isset($files[$fileKey])) {
                $this->storeDocument($user, $files[$fileKey], $documentType);
            }
        }
    }

    private function storeDocument(User $user, UploadedFile $file, string $type): UserDocument
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
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

    public function approveKyc(string $userId, string $comment = null): User
    {
        $user = User::findOrFail($userId);
        $user->update([
            'statut_kyc' => KycStatus::VALIDE,
            'statut' => 'actif'
        ]);

        return $user;
    }

    public function rejectKyc(string $userId, string $reason): User
    {
        $user = User::findOrFail($userId);
        $user->update([
            'statut_kyc' => KycStatus::REJETE,
            'statut' => 'suspendu'
        ]);

        return $user;
    }
}