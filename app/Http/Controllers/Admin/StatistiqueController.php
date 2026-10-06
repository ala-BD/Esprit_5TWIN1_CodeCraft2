<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatistiquesUtilisateurs;
use Illuminate\View\View;

class StatistiqueController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /admin/statistiques — Statistiques des utilisateurs
    |------------------------------------------------------------------
    */
    public function index(StatistiquesUtilisateurs $statistiques): View
    {
        return view('admin.statistiques', $statistiques->calculer());
    }
}
