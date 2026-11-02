<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Logistique\BriefingPrevision;
use App\Services\Logistique\PrevisionDons;
use Illuminate\View\View;

class PrevisionController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /admin/logistique/previsions — Prévision du volume de dons
    |------------------------------------------------------------------
    */
    public function index(PrevisionDons $previsions, BriefingPrevision $briefing): View
    {
        $prevision = $previsions->prevoir();

        return view('admin.logistique.previsions', [
            'prevision' => $prevision,
            'capacite'  => $briefing->capacite($prevision),
            'briefing'  => $prevision['semaines_historique'] >= 4 ? $briefing->rediger($prevision) : null,
        ]);
    }
}
