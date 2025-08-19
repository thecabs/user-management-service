<?php

namespace App\Enums;

enum KycStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_COURS = 'en_cours';
    case VALIDE = 'valide';
    case REJETE = 'rejete';
    
    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_COURS => 'En cours de vérification',
            self::VALIDE => 'Validé',
            self::REJETE => 'Rejeté',
        };
    }
}