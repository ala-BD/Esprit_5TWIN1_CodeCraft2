<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string|null $prenom
 * @property string $email
 * @property string|null $telephone
 * @property string $role
 * @property bool $actif
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read string $initials
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Adresse> $adresses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DonVetement> $dons
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Article> $articles
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /*
    |----------------------------------------------------------------------
    | Rôles disponibles dans RETISS
    |----------------------------------------------------------------------
    */
    const ROLE_DONATEUR   = 'DONATEUR';
    const ROLE_CLIENT     = 'CLIENT';
    const ROLE_COLLECTEUR = 'COLLECTEUR';
    const ROLE_ATELIER    = 'ATELIER';
    const ROLE_RECYCLEUR  = 'RECYCLEUR';
    const ROLE_ADMIN      = 'ADMIN';

    /*
    |----------------------------------------------------------------------
    | Mass assignable
    |----------------------------------------------------------------------
    */
    protected $fillable = [
        'name',
        'prenom',
        'email',
        'telephone',
        'role',
        'actif',
        'password',
    ];

    /*
    |----------------------------------------------------------------------
    | Attributs cachés (sérialisation JSON)
    |----------------------------------------------------------------------
    */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |----------------------------------------------------------------------
    | Casts
    |----------------------------------------------------------------------
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'actif'             => 'boolean',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Helpers rôles
    |----------------------------------------------------------------------
    */

    /** Vérifie si l'utilisateur a un rôle donné */
    public function hasRole(string $role): bool
    {
        return $this->role === strtoupper($role);
    }

    /** Vérifie si l'utilisateur est admin */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Retourne le nom complet (prénom + nom) */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->prenom} {$this->name}");
    }

    /** Retourne les initiales pour l'avatar */
    public function getInitialsAttribute(): string
    {
        $prenom = $this->prenom ? strtoupper(substr($this->prenom, 0, 1)) : '';
        $nom    = $this->name   ? strtoupper(substr($this->name,   0, 1)) : '';
        return $prenom . $nom;
    }

    /*
    |----------------------------------------------------------------------
    | Relations
    |----------------------------------------------------------------------
    */

    /**
     * Relation 1-N : Un utilisateur possède plusieurs adresses.
     */
    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class);
    }

    /**
     * Récupère l'adresse par défaut de l'utilisateur (le cas échéant).
     */
    public function adresseParDefaut(): HasOne
    {
        return $this->hasOne(Adresse::class)->where('par_defaut', true);
    }

    /**
     * Relation 1-N : Dons de vêtements de l'utilisateur.
     */
    public function donVetements(): HasMany
    {
        return $this->hasMany(DonVetement::class);
    }

    /**
     * Alias de donVetements()
     */
    public function dons(): HasMany
    {
        return $this->hasMany(DonVetement::class);
    }

    /**
     * Relation 1-N : Articles publiés par l'utilisateur.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
