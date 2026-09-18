<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List the authenticated user's notifications, newest first. Always
     * scoped to the current user, so there is no separate authorization
     * check needed here (unlike show/markAsRead, which take an id).
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read. authorize() confirms the
     * notification actually belongs to the current user before touching it.
     */
    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark every unread notification belonging to the current user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
