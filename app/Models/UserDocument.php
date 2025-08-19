<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type_document',
        'nom_fichier',
        'chemin_fichier',
        'mime_type',
        'taille_fichier',
        'statut_verification',
        'commentaire_rejet',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->chemin_fichier);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}