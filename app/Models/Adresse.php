<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adresse extends Model
{
    use HasFactory;

    /**
     * Nom de la table associée au modèle.
     */
    protected $table = 'adresses';

    /**
     * Attributs assignables en masse.
     */
    protected $fillable = [
        'user_id',
        'libelle',
        'rue',
        'ville',
        'code_postal',
        'latitude',
        'longitude',
        'par_defaut',
    ];

    /**
     * Casts des attributs.
     */
    protected function casts(): array
    {
        return [
            'latitude'   => 'float',
            'longitude'  => 'float',
            'par_defaut' => 'boolean',
        ];
    }

    /**
     * Relation N-1 : Une adresse appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur pour afficher l'adresse complète formatée.
     */
    public function getFormattedAddressAttribute(): string
    {
        return "{$this->rue}, {$this->code_postal} {$this->ville}";
    }

    /**
     * Définit cette adresse comme l'adresse par défaut de l'utilisateur,
     * et retire le statut par défaut de toutes ses autres adresses.
     */
    public function makeDefault(): void
    {
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['par_defaut' => false]);

        $this->update(['par_defaut' => true]);
    }
}
