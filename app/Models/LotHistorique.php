<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotHistorique extends Model
{
    protected $fillable = [
        'lot_textile_id',
        'user_id',
        'evenement',
        'champ',
        'ancienne_valeur',
        'nouvelle_valeur',
        'description',
        'icone',
        'couleur',
    ];

    /*
    |------------------------------------------------------------------
    | Constantes événements
    |------------------------------------------------------------------
    */
    const CREATION          = 'CREATION';
    const STATUT_CHANGE     = 'STATUT_CHANGE';
    const ETAPE_AJOUTEE     = 'ETAPE_AJOUTEE';
    const ETAPE_TERMINEE    = 'ETAPE_TERMINEE';
    const PASSEPORT_GENERE  = 'PASSEPORT_GENERE';
    const DON_RATTACHE      = 'DON_RATTACHE';
    const DON_DETACHE       = 'DON_DETACHE';
    const MODIFICATION      = 'MODIFICATION';

    /*
    |------------------------------------------------------------------
    | Config icône + couleur par événement
    |------------------------------------------------------------------
    */
    const CONFIGS = [
        self::CREATION         => ['icone' => 'fa-plus-circle',    'couleur' => 'green'],
        self::STATUT_CHANGE    => ['icone' => 'fa-exchange-alt',   'couleur' => 'blue'],
        self::ETAPE_AJOUTEE    => ['icone' => 'fa-play-circle',    'couleur' => 'indigo'],
        self::ETAPE_TERMINEE   => ['icone' => 'fa-check-circle',   'couleur' => 'green'],
        self::PASSEPORT_GENERE => ['icone' => 'fa-qrcode',         'couleur' => 'purple'],
        self::DON_RATTACHE     => ['icone' => 'fa-link',           'couleur' => 'teal'],
        self::DON_DETACHE      => ['icone' => 'fa-unlink',         'couleur' => 'orange'],
        self::MODIFICATION     => ['icone' => 'fa-edit',           'couleur' => 'amber'],
    ];

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */
    public function lotTextile(): BelongsTo
    {
        return $this->belongsTo(LotTextile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |------------------------------------------------------------------
    | Helper : créer une entrée d'historique
    |------------------------------------------------------------------
    */
    public static function enregistrer(
        LotTextile $lot,
        string     $evenement,
        string     $description,
        ?string    $champ = null,
        ?string    $ancienneValeur = null,
        ?string    $nouvelleValeur = null,
    ): self {
        $config = self::CONFIGS[$evenement] ?? ['icone' => 'fa-history', 'couleur' => 'gray'];

        return self::create([
            'lot_textile_id'  => $lot->id,
            'user_id'         => auth()->id(),
            'evenement'       => $evenement,
            'champ'           => $champ,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'description'     => $description,
            'icone'           => $config['icone'],
            'couleur'         => $config['couleur'],
        ]);
    }

    /*
    |------------------------------------------------------------------
    | Helper : classes Tailwind selon couleur
    |------------------------------------------------------------------
    */
    public function getBadgeAttribute(): array
    {
        return match($this->couleur) {
            'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'dot' => 'bg-green-500'],
            'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600',   'dot' => 'bg-blue-500'],
            'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'dot' => 'bg-indigo-500'],
            'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'dot' => 'bg-purple-500'],
            'teal'   => ['bg' => 'bg-teal-100',   'text' => 'text-teal-600',   'dot' => 'bg-teal-500'],
            'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'dot' => 'bg-orange-500'],
            'amber'  => ['bg' => 'bg-amber-100',  'text' => 'text-amber-600',  'dot' => 'bg-amber-500'],
            'red'    => ['bg' => 'bg-red-100',     'text' => 'text-red-600',    'dot' => 'bg-red-500'],
            default  => ['bg' => 'bg-gray-100',   'text' => 'text-gray-600',   'dot' => 'bg-gray-400'],
        };
    }
}
