<?php

namespace App\Http\Middleware;

use App\Services\BankAccountsClient;
use Closure;
use Illuminate\Http\Request;

class RequireBankClient
{
    public function __construct(private BankAccountsClient $client) {}

    public function handle(Request $request, Closure $next)
    {
        $ext = $request->attributes->get('external_id')
            ?? $request->attributes->get('sub')
            ?? data_get($request->attributes->get('token_data'), 'sub');

        if (!$ext) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Vérif live auprès de bankaccount-service (cache possible si tu veux)
        $ok = $this->client->isBankClient($ext);

        if (!$ok) {
            return response()->json([
                'error'   => 'Forbidden',
                'reason'  => 'not_bank_client',
                'message' => 'User does not have a verified bank account',
            ], 403);
        }

        return $next($request);
    }
}
