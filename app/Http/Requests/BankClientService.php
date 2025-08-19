<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BankClientService
{
    public function __construct(
        private BankAccountsClient $accounts,
        private CeilingsClient $ceilings,
        private KeycloakAdminService $kcAdmin // si tu gères l’assignation du rôle KC ici
    ) {}

    public function promoteToBankClient(string $externalId, string $currency = 'XAF'): bool
    {
        if (!$this->accounts->isBankClient($externalId)) {
            return false; // pas de compte vérifié -> on ne promeut pas
        }

        // Assigner le rôle Keycloak "client_bancaire" (optionnel si déjà fait ailleurs)
        // $this->kcAdmin->addRealmRole($externalId, 'client_bancaire');

        // Garantir le plafond par défaut
        $ensured = $this->ceilings->ensureDefault($externalId, $currency);
        Log::info('bankclient.promote', [
            'external_id' => $externalId,
            'ceilings_ensured' => $ensured,
        ]);

        return true;
    }
}
