<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Adresse> $adresses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DonVetement> $dons
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Article> $articles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commande> $commandes
 * @property-read \App\Models\Panier|null $panier
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

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
        'tarif_horaire',
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
            'tarif_horaire'     => 'decimal:2',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Rôles Spatie : la colonne users.role reste la source de vérité.
    | À chaque enregistrement qui change le rôle, le rôle Spatie est
    | synchronisé (un seul rôle par utilisateur).
    |----------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $user->synchroniserRoleSpatie();
            }
        });
    }

    public function synchroniserRoleSpatie(): void
    {
        // Colonne non renseignée à la création : on relit la valeur par défaut de la base
        $role = $this->role ?: static::whereKey($this->getKey())->value('role');

        if ($role) {
            $this->syncRoles([Role::findOrCreate($role, 'web')]);
        }
    }

    /*
    |----------------------------------------------------------------------
    | Helpers rôles
    |----------------------------------------------------------------------
    */

    /**
     * Vérifie le rôle à partir de la colonne users.role (sans requête).
     * Renommée depuis hasRole() pour laisser hasRole() au trait Spatie HasRoles.
     */
    public function aLeRole(string $role): bool
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
        $nom    = $this->name ? strtoupper(substr($this->name,   0, 1)) : '';
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

    /**
     * Relation 1-1 : Le panier de l'utilisateur (créé à la demande).
     */
    public function panier(): HasOne
    {
        return $this->hasOne(Panier::class);
    }

    /**
     * Relation 1-N : Avis rédigés par l'utilisateur.
     */
    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }

    /**
     * Relation 1-N : Votes émis par l'utilisateur (likes / dislikes).
     */
    public function avisVotes(): HasMany
    {
        return $this->hasMany(AvisVote::class);
    }

    /**
     * Relation HasManyThrough : Avis reçus sur les articles vendus par cet utilisateur.
     */
    public function avisRecus(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Avis::class, Article::class, 'user_id', 'article_id');
    }

    /**
     * Note moyenne de l'Atelier / Vendeur (calculée sur l'ensemble de ses articles).
     */
    public function noteMoyenne(): float
    {
        $avg = $this->avisRecus()->avg('note_globale');
        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Nombre total d'avis reçus sur ses articles.
     */
    public function nbAvisRecus(): int
    {
        return $this->avisRecus()->count();
    }

    /**
     * Relation 1-N : Articles mis en favoris par l'utilisateur.
     */
    public function favoris(): HasMany
    {
        return $this->hasMany(Favori::class);
    }

    /**
     * Relation 1-N : Recherches sauvegardées par l'utilisateur.
     */
    public function recherchesSauvegardees(): HasMany
    {
        return $this->hasMany(RechercheSauvegardee::class);
    }

    /**
     * Relation 1-N : Estimations générées par cet atelier/utilisateur.
     */
    public function estimations(): HasMany
    {
        return $this->hasMany(Estimation::class);
    }

    /**
     * Tarif horaire effectif de l'atelier (utilise le tarif configuré ou le barème par défaut).
     */
    public function getTarifHoraireEffectifAttribute(): float
    {
        if ($this->tarif_horaire !== null && (float) $this->tarif_horaire > 0) {
            return (float) $this->tarif_horaire;
        }

        $param = ParametreCout::where('matiere', 'defaut')->first() ?? ParametreCout::first();
        return $param ? (float) $param->tarif_horaire_defaut : 15.0;
    }
}
