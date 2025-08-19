<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankingClientRequest;
use App\Http\Requests\NonBankingClientRequest;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;

class OnboardingController extends Controller
{
    public function __construct(private KycService $kycService)
    {
    }

    public function registerBankingClient(BankingClientRequest $request): JsonResponse
    {
        try {
            $user = $this->kycService->createBankingClient($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Client bancaire enregistré avec succès',
                'data' => [
                    'user_id' => $user->id,
                    'statut_kyc' => $user->statut_kyc->value,
                    'type_client' => $user->type_client->value,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerNonBankingClient(NonBankingClientRequest $request): JsonResponse
    {
        try {
            $files = [
                'piece_face_avant' => $request->file('piece_face_avant'),
                'piece_face_arriere' => $request->file('piece_face_arriere'),
                'selfie' => $request->file('selfie'),
            ];

            $user = $this->kycService->createNonBankingClient(
                $request->validated(),
                $files
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande d\'ouverture de compte soumise avec succès',
                'data' => [
                    'user_id' => $user->id,
                    'statut_kyc' => $user->statut_kyc->value,
                    'type_client' => $user->type_client->value,
                    'message_info' => 'Votre dossier est en cours de vérification. Vous recevrez une notification une fois validé.'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}