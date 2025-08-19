<?php

namespace App\Models;

use App\Enums\ClientType;
use App\Enums\DocumentType;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;  // <-- important

class User extends Authenticatable
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'external_id',
        'nom',
        'prenom',
        'date_naissance',
        'email',
        'telephone',
        'type_client',
        'numero_compte_bancaire',
        'telephone_cbs',
        'solde_min_declare',
        'solde_max_declare',
        'type_piece',
        'numero_piece_identite',
        'niu',
        'date_expiration_piece',
        'recommande_par_tel',
        'statut',
        'statut_kyc',
    ];

    protected $casts = [
        'id' => 'string',
        'external_id' => 'string',
        'date_naissance' => 'date',
        'date_expiration_piece' => 'date',
        'type_client' => ClientType::class,
        'type_piece' => DocumentType::class,
        'statut_kyc' => KycStatus::class,
        'solde_min_declare' => 'decimal:2',
        'solde_max_declare' => 'decimal:2',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function contacts(): HasOne
    {
        return $this->hasOne(UserContact::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function isBankingClient(): bool
    {
        return $this->type_client === ClientType::BANCAIRE;
    }

    public function isKycComplete(): bool
    {
        return $this->statut_kyc === KycStatus::VALIDE;
    }

    public function scopeBankingClients($query)
    {
        return $query->where('type_client', ClientType::BANCAIRE);
    }

    public function scopeNonBankingClients($query)
    {
        return $query->where('type_client', ClientType::NON_BANCAIRE);
    }

     public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }
}