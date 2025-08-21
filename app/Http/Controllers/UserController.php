<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Requests\DocumentUploadRequest;
use App\Models\User;
use App\Services\UserService;
use App\Services\KeycloakAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private KeycloakAdminService $kcAdmin
    ) {}

    /** 3) app/Services/KeycloakTokenService.php (complet)
     * 4) app/Services/KeycloakAdminService.php (complet)
     * Legacy public: existence dans la base locale par external_id.
     * Conserve exactement ton comportement antérieur.
     */
    public function checkExternalUser(string $externalId): JsonResponse
    {
        $userExists = \App\Models\User::where('external_id', $externalId)->exists();
        return response()->json(['exists' => $userExists]);
    }

    /**
     * Technique (JWT requis, pas de rôle) : existence dans Keycloak Admin API.
     * Répond 200 avec data si trouvé, sinon 404.
     */
    public function getByExternalId(string $external_id): JsonResponse
    {
        $user = $this->kcAdmin->getUserById($external_id);

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }

        // Subset "safe"
        $data = [
            'id'         => $user['id'] ?? $external_id,
            'username'   => $user['username'] ?? null,
            'email'      => $user['email'] ?? null,
            'enabled'    => $user['enabled'] ?? null,
            'firstName'  => $user['firstName'] ?? null,
            'lastName'   => $user['lastName'] ?? null,
            'attributes' => $user['attributes'] ?? new \stdClass(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    // public function profile(): JsonResponse
    // {
    //     /** @var User|null $user */
    //     $user = Auth::user();
    //     if (!$user) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }
    //     return response()->json($user->load(['addresses', 'contacts', 'documents']));
    // }

    public function profile(Request $request): JsonResponse
{
    // Données token & identités normalisées par le middleware
    $td = (array) $request->attributes->get('token_data', []);
    $externalId = (string) ($request->attributes->get('external_id') ?? data_get($td, 'sub', ''));

    if ($externalId === '') {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // ⚠️ NE PAS appeler ->load() sur Auth::user() (GenericUser)
    // On récupère le profil local éventuellement associé à l'external_id
    $local = User::query()->where('external_id', $externalId)->first();

    // Réponse unifiée : claims Keycloak + profil local si présent
    return response()->json([
        'success' => true,
        'data' => [
            'external_id' => $externalId,
            'username'    => (string) data_get($td, 'preferred_username', ''),
            'email'       => (string) data_get($td, 'email', ''),
            'roles'       => (array)  data_get($td, 'realm_access.roles', []),
            'acr'         => (string) data_get($td, 'acr', ''),
            'amr'         => (array)  data_get($td, 'amr', []),

            // Profil local si existant
            'local_profile' => $local ? [
                'id'         => $local->id,
                'nom'        => $local->nom,
                'prenom'     => $local->prenom,
                'email'      => $local->email,
                'telephone'  => $local->telephone,
                'created_at' => optional($local->created_at)->toIso8601String(),
                'updated_at' => optional($local->updated_at)->toIso8601String(),
            ] : null,
        ],
    ]);
}
    public function updateProfile(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = request()->validate([
            'nom'        => 'sometimes|string|max:255',
            'prenom'     => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,'.$user->id,
            'telephone'  => 'sometimes|string|unique:users,telephone,'.$user->id,
        ]);

        $updatedUser = $this->userService->updateProfile($user, $validated);
        return response()->json($updatedUser);
    }

    public function getDocuments(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return response()->json($user->documents);
    }

    public function uploadDocument(DocumentUploadRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $file     = $request->file('document');
        $type     = $request->input('type_document');
        $document = $this->userService->uploadDocument($user, $file, $type);

        return response()->json($document, 201);
    }

    public function index(): JsonResponse
    {
        $users = User::all();
        return response()->json($users);
    }
}
