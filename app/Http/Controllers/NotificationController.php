<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** Lista todas las notificaciones del usuario. */
    public function index(Request $request): View
    {
        return view('notificaciones.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /** Marca todas como leídas. */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    /** Abre una notificación: la marca leída y redirige a su destino. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }
}
