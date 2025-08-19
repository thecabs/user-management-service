<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KycController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DeviceTrustController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health (public)
Route::get('/health', fn () => response()->json([
    'service'   => 'User Management Service (KYC)',
    'status'    => 'OK',
    'timestamp' => now()->toIso8601String(),
]))->name('health.check');

// Legacy public: existence locale (DB)
Route::get('/users/external/{externalId}', [UserController::class, 'checkExternalUser'])
    ->whereUuid('externalId')
    ->name('users.exists.local');

// Zone protégée Keycloak
Route::middleware(['keycloak'])->group(function () {

    // Technique: existence via Keycloak Admin API (pas de check.role)
    Route::get('/users/external/{external_id}/kc', [UserController::class, 'getByExternalId'])
        ->whereUuid('external_id')
        ->name('users.exists.keycloak');

    // ZTA pour PII
    Route::middleware(['context.enricher', 'resource.tag:PII', 'pdp'])->group(function () {

        // Onboarding
        Route::post('/onboarding/banking-client', [OnboardingController::class, 'registerBankingClient'])
            ->middleware('check.role:client_bancaire')
            ->name('onboarding.banking');

        Route::post('/onboarding/non-banking-client', [OnboardingController::class, 'registerNonBankingClient'])
            ->middleware('check.role:client_non_bancaire')
            ->name('onboarding.non_banking');

        // Profil & documents (clients)
        Route::middleware('check.role:client_bancaire,client_non_bancaire')->group(function () {
            Route::get('/users/profile',    [UserController::class, 'profile'])->name('users.profile.show');
            Route::put('/users/profile',    [UserController::class, 'updateProfile'])->name('users.profile.update');
            Route::get('/users/documents',  [UserController::class, 'getDocuments'])->name('users.docs.index');
            Route::post('/users/documents', [UserController::class, 'uploadDocument'])->name('users.docs.upload');

            // Trusted devices
            Route::get('/devices',        [DeviceTrustController::class, 'index'])->name('devices.index');
            Route::post('/devices/trust', [DeviceTrustController::class, 'trust'])
                ->middleware(['throttle:api', 'idempotency:600'])
                ->name('devices.trust');
        });

        // Admin KYC
        Route::middleware('check.role:admin')->group(function () {
            Route::get('/kyc/pending',           [KycController::class, 'getPendingKyc'])->name('kyc.pending');
            Route::post('/kyc/{userId}/approve', [KycController::class, 'approveKyc'])->name('kyc.approve');
            Route::post('/kyc/{userId}/reject',  [KycController::class, 'rejectKyc'])->name('kyc.reject');
            Route::get('/users',                 [UserController::class, 'index'])->name('users.index');

            // (option) si tu ajoutes l’implémentation :
            // Route::post('/internal/users/sync', [UserController::class, 'syncFromKeycloak'])->name('users.sync');
        });
    });
});
