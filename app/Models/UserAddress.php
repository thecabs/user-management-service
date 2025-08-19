<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'region',
        'ville',
        'quartier',
        'adresse_complete',
        'adresse_principale'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}