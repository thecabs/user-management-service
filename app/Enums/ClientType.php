<?php

namespace App\Enums;

enum ClientType: string
{
    case BANCAIRE = 'bancaire';
    case NON_BANCAIRE = 'non_bancaire';
    
    public function label(): string
    {
        return match($this) {
            self::BANCAIRE => 'Client Bancaire',
            self::NON_BANCAIRE => 'Client Non-Bancaire',
        };
    }
}