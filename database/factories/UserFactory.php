<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'external_id' => Str::uuid(),
            'nom' => $this->faker->lastName,
            'prenom' => $this->faker->firstName,
            'email' => $this->faker->unique()->safeEmail,
            'telephone' => $this->faker->unique()->phoneNumber,
            'type_client' => $this->faker->randomElement([
                ClientType::BANCAIRE->value,
                ClientType::NON_BANCAIRE->value
            ]),
            'statut' => 'actif',
            'statut_kyc' => $this->faker->randomElement([
                KycStatus::EN_ATTENTE->value,
                KycStatus::VALIDE->value,
                KycStatus::REJETE->value
            ]),
            'date_naissance' => $this->faker->date(),
        ];
    }

    public function bankingClient(): static
    {
        return $this->state([
            'type_client' => ClientType::BANCAIRE->value,
            'numero_compte_bancaire' => $this->faker->bankAccountNumber,
            'telephone_cbs' => $this->faker->phoneNumber,
            'solde_min_declare' => $this->faker->randomFloat(2, 1000, 5000),
            'solde_max_declare' => $this->faker->randomFloat(2, 5000, 10000),
        ]);
    }

    public function nonBankingClient(): static
    {
        return $this->state([
            'type_client' => ClientType::NON_BANCAIRE->value,
            'type_piece' => 'cni',
            'numero_piece_identite' => $this->faker->randomNumber(8),
            'date_expiration_piece' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
        ]);
    }
}