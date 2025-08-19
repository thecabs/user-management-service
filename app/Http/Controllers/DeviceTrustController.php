<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrustDeviceRequest;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeviceTrustController extends Controller
{
    private function externalId(Request $r): ?string
    {
        return $r->attributes->get('external_id')
            ?? $r->header('X-User-Id'); // fallback
    }

    // private function ensureLocalUser(Request $r, string $externalId): User
    // {
    //     $defaults = [
    //         // on capture quelques infos du token si dispo, sans casser ton schéma
    //         'nom'        => $r->attributes->get('token_data')->family_name ?? null,
    //         'prenom'     => $r->attributes->get('token_data')->given_name ?? null,
    //         'email'      => $r->attributes->get('token_data')->email ?? null,
    //         'telephone'  => $r->attributes->get('token_data')->phone_number ?? null,
    //     ];

    //     $user = User::firstOrCreate(['external_id' => $externalId], array_filter($defaults, fn($v) => !is_null($v)));

    //     // Mise à jour soft si de nouvelles infos arrivent
    //     $user->fill(array_filter($defaults, fn($v) => !is_null($v)))->save();

    //     return $user;
    // }

    private function ensureLocalUser(Request $r, string $externalId): User
{
    $td = $r->attributes->get('token_data') ?? [];
    $defaults = [
        'nom'       => data_get($td, 'family_name'),
        'prenom'    => data_get($td, 'given_name'),
        'email'     => data_get($td, 'email'),
        'telephone' => data_get($td, 'phone_number'),
    ];

    $user = User::firstOrCreate(['external_id' => $externalId], array_filter($defaults));
    $user->fill(array_filter($defaults))->save();

    return $user;
}


    /** GET /devices — liste des équipements de l’utilisateur */
    public function index(Request $request): JsonResponse
    {
        $externalId = $this->externalId($request);
        if (!$externalId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('external_id', $externalId)->first();
        if (!$user) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $devices = $user->trustedDevices()->orderByDesc('last_seen_at')->get();

        return response()->json([
            'data' => $devices->map(function (TrustedDevice $d) {
                return [
                    'id'               => $d->id,
                    'fingerprint'      => $d->fingerprint,
                    'device_name'      => $d->device_name,
                    'platform'         => $d->platform,
                    'is_trusted'       => (bool) $d->is_trusted,
                    'trust_level'      => $d->trust_level,
                    'risk_score'       => (int) $d->risk_score,
                    'mfa_verified'     => (bool) $d->mfa_verified,
                    'first_trusted_at' => $d->first_trusted_at?->toIso8601String(),
                    'last_seen_at'     => $d->last_seen_at?->toIso8601String(),
                    'created_at'       => $d->created_at?->toIso8601String(),
                    'updated_at'       => $d->updated_at?->toIso8601String(),
                ];
            }),
            'meta' => ['total' => $devices->count()],
        ]);
    }

    /** POST /devices/trust — upsert idempotent */
    public function trust(TrustDeviceRequest $request): JsonResponse
    {
        $externalId = $this->externalId($request);
        if (!$externalId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->ensureLocalUser($request, $externalId);

        $fp         = (string) $request->string('fingerprint');
        $deviceName = (string) $request->string('device_name', 'Unknown');
        $platform   = (string) $request->string('platform', 'unknown');
        $ua         = (string) ($request->input('user_agent') ?: $request->userAgent());
        $ip         = (string) $request->ip();

        $mfaHeader = strtolower((string) $request->header('X-MFA-Passed', 'false')) === 'true';
        $mfaBody   = (bool) $request->boolean('mfa_passed', false);
        $mfaPassed = $mfaHeader || $mfaBody;

        $trustLevel = $mfaPassed ? 'high' : 'low';

        $device = TrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('fingerprint', $fp)
            ->first();

        if (!$device) {
            $device = TrustedDevice::create([
                'user_id'         => $user->id,
                'fingerprint'     => $fp,
                'device_name'     => $deviceName,
                'platform'        => $platform,
                'is_trusted'      => true,
                'trust_level'     => $trustLevel,
                'risk_score'      => 0,
                'mfa_verified'    => $mfaPassed,
                'first_trusted_at'=> now(),
                'last_seen_at'    => now(),
                'ip'              => $ip,
                'user_agent'      => $ua,
            ]);
        } else {
            $device->fill([
                'device_name'  => $deviceName ?: $device->device_name,
                'platform'     => $platform ?: $device->platform,
                'is_trusted'   => true,
                'trust_level'  => $trustLevel,
                'mfa_verified' => $mfaPassed || $device->mfa_verified,
                'last_seen_at' => now(),
                'ip'           => $ip,
                'user_agent'   => $ua,
            ])->save();
        }

        Log::channel('audit')->info('trusted_device.upsert', [
            'request_id'  => $request->header('X-Request-Id') ?: (string) Str::uuid(),
            'external_id' => $externalId,
            'device_id'   => $device->id,
            'fp_hint'     => substr($device->fingerprint, 0, 6) . '***',
            'trust_level' => $device->trust_level,
            'mfa_verified'=> $device->mfa_verified,
        ]);

        return response()->json([
            'message' => 'Trusted device enregistré',
            'data' => [
                'id'               => $device->id,
                'fingerprint'      => $device->fingerprint,
                'device_name'      => $device->device_name,
                'platform'         => $device->platform,
                'is_trusted'       => (bool) $device->is_trusted,
                'trust_level'      => $device->trust_level,
                'risk_score'       => (int) $device->risk_score,
                'mfa_verified'     => (bool) $device->mfa_verified,
                'first_trusted_at' => $device->first_trusted_at?->toIso8601String(),
                'last_seen_at'     => $device->last_seen_at?->toIso8601String(),
            ]
        ], 201);
    }
}
