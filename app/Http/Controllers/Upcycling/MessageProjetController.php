<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\MessageProjetRequest;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\GestionProjetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MessageProjetController extends Controller
{
    public function __construct(private GestionProjetService $gestion) {}

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/messages
    | Message au fil du projet ; avec "ia=1", le styliste IA répond.
    |------------------------------------------------------------------
    */
    public function store(MessageProjetRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        Gate::authorize('discuter', $projet);

        $this->gestion->ecrireMessage($projet, Auth::user(), $request->validated('contenu'), $request->boolean('ia'));

        return redirect()->to(route('upcycling.projets.show', $projet) . '#discussion');
    }
}
