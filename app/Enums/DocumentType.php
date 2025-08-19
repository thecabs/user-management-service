<?php

namespace App\Enums;

enum DocumentType: string
{
    case CNI = 'cni';
    case PASSEPORT = 'passeport';
    case RECU_CARTE_NATIONALE = 'recu_carte_nationale';
    
    public function label(): string
    {
        return match($this) {
            self::CNI => 'Carte Nationale d\'Identité',
            self::PASSEPORT => 'Passeport',
            self::RECU_CARTE_NATIONALE => 'Reçu de Carte Nationale',
        };
    }
}