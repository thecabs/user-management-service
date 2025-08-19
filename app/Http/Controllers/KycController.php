<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    public function __construct(private KycService $kycService) {}

    public function getPendingKyc(): JsonResponse
    {
        $users = User::where('statut_kyc', 'en_attente')->get();
        return response()->json($users);
    }

    public function approveKyc(string $userId): JsonResponse
    {
        $user = $this->kycService->approveKyc($userId, request('comment'));
        return response()->json(['message' => 'KYC approved', 'user' => $user]);
    }

    public function rejectKyc(string $userId): JsonResponse
    {
        $user = $this->kycService->rejectKyc($userId, request('reason'));
        return response()->json(['message' => 'KYC rejected', 'user' => $user]);
    }
}