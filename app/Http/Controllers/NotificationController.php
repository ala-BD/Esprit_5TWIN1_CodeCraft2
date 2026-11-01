<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Centre de notifications in-app (commun à tous les modules).
 */
class NotificationController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /notifications/resume — Compteur + dernières notifications (AJAX)
    |------------------------------------------------------------------
    */
    public function resume(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $dernieres = $user->notifications()->latest()->take(8)->get();

        return response()->json([
            'non_lues' => $user->unreadNotifications()->count(),
            'derniere' => $dernieres->first(fn ($n) => $n->read_at === null)?->data['message'] ?? null,
            'html'     => view('components.notifications-liste', ['dernieres' => $dernieres])->render(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /notifications/{id} — Marquer comme lue puis ouvrir le lien
    |------------------------------------------------------------------
    */
    public function lire(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    /*
    |------------------------------------------------------------------
    | POST /notifications/tout-lire
    |------------------------------------------------------------------
    */
    public function toutLire(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    }
}
