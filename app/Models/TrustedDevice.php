<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'fingerprint',
        'device_name',
        'platform',
        'is_trusted',
        'trust_level',
        'risk_score',
        'mfa_verified',
        'first_trusted_at',
        'last_seen_at',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'is_trusted'       => 'boolean',
        'mfa_verified'     => 'boolean',
        'risk_score'       => 'integer',
        'first_trusted_at' => 'datetime',
        'last_seen_at'     => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
