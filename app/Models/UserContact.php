<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContact extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'telephone_recuperation',
        'email_recuperation',
        'contacts_urgence'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}