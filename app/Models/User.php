<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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

    const ROLES = [
        self::ROLE_DONATEUR,
        self::ROLE_CLIENT,
        self::ROLE_COLLECTEUR,
        self::ROLE_ATELIER,
        self::ROLE_RECYCLEUR,
        self::ROLE_ADMIN,
    ];

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

    /** Tournées effectuées par le collecteur (M5) */
    public function tournees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tournee::class);
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
}
