<?php

/*
|--------------------------------------------------------------------------
| M5 Logistique
|--------------------------------------------------------------------------
*/

return [

    // Géocodage des adresses écrites (Nominatim / OpenStreetMap, gratuit)
    'geocodage' => [
        'actif'      => (bool) env('GEOCODAGE_ACTIF', true),
        'url'        => env('GEOCODAGE_URL', 'https://nominatim.openstreetmap.org/search'),
        'pays'       => env('GEOCODAGE_PAYS', 'tn'),
        // Nominatim exige un User-Agent qui identifie l'application
        'user_agent' => env('GEOCODAGE_USER_AGENT', 'RETISS-Logistique/1.0 (projet etudiant ESPRIT)'),
    ],

    // Point de départ et de retour des tournées
    'depot' => [
        'nom'       => env('LOGISTIQUE_DEPOT_NOM', 'Entrepôt RETISS — Charguia'),
        'latitude'  => (float) env('LOGISTIQUE_DEPOT_LAT', 36.8380),
        'longitude' => (float) env('LOGISTIQUE_DEPOT_LNG', 10.2070),
        'retour'    => (bool) env('LOGISTIQUE_RETOUR_DEPOT', true),
    ],

    // Hypothèses de l'optimisation d'itinéraire (circulation urbaine)
    'itineraire' => [
        // Les routes ne sont pas droites : distance réelle ≈ distance à vol d'oiseau × ce facteur
        'facteur_detour'   => 1.3,
        'vitesse_kmh'      => 25,
        'minutes_par_arret' => 10,
        'heure_depart'     => '08:30',
    ],

    // Affectation automatique des commandes et des dons aux collecteurs
    'affectation' => [
        'arrets_max'          => 12,    // capacité d'une tournée
        'cout_par_arret_km'   => 3.0,   // équilibre de charge : chaque arrêt déjà prévu « coûte » cette distance
        'cout_ouverture_km'   => 8.0,   // ouvrir une nouvelle tournée plutôt que compléter une existante
    ],

];
