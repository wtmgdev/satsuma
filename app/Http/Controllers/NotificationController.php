<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotificationCollection;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'notifications' => collect($user->notifications()->limit(10)->get())->values(),
        ]);
    }

    public function markAllRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markRead();

        return response()->json(['unread' => 0]);
    }
}
