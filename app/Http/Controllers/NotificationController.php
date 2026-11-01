<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Centre de notifications in-app (commun à tous les modules).
 */
class NotificationController extends Controller
{
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
