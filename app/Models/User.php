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
 * @property-read string $full_name
 * @property-read string $initials
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

    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function hasRole(string $role): bool
    {
        return $this->role === strtoupper($role);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Roles autorisés à créer/gérer des articles (M2) */
    public function canCreateArticle(): bool
    {
        return in_array($this->role, [self::ROLE_ATELIER, self::ROLE_DONATEUR, self::ROLE_ADMIN]);
    }

    /** Remise automatique 10% sur articles Atelier (M2) */
    public function getsRemise(): bool
    {
        return in_array($this->role, Commande::ROLES_AVEC_REMISE);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->prenom} {$this->name}");
    }

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

    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class);
    }

    public function adresseParDefaut(): HasOne
    {
        return $this->hasOne(Adresse::class)->where('par_defaut', true);
    }

    public function donVetements(): HasMany
    {
        return $this->hasMany(DonVetement::class);
    }

    public function dons(): HasMany
    {
        return $this->hasMany(DonVetement::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    /** Tournées effectuées par le collecteur (M5) */
    public function tournees(): HasMany
    {
        return $this->hasMany(Tournee::class);
    }
}
