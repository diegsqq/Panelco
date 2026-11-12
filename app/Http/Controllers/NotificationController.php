<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->take(50)
            ->get()
            ->map(function (Notification $n) {
                $type = $n->type;
                $category = 'info';
                $icon = 'bi-info';

                if (Str::startsWith($type, 'auth.login')) {
                    $category = 'warning';
                    $icon = 'bi-shield-lock';
                } elseif (Str::startsWith($type, 'user.')) {
                    $category = 'secondary';
                    $icon = 'bi-person';
                } elseif (Str::startsWith($type, 'document.')) {
                    $category = 'primary';
                    $icon = 'bi-folder';
                } elseif (Str::startsWith($type, 'password')) {
                    $category = 'danger';
                    $icon = 'bi-lock';

                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at?->toISOString(),
                    'readable_created_at' => $n->created_at?->diffForHumans(),
                    'category' => $category,
                    'icon' => $icon,
                ];
            });

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (!$notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        $request->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
