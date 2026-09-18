<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Avatars live on the private disk like every other upload in this app, so
 * viewing one — even your own — goes through this authenticated route
 * rather than a public URL.
 */
class AvatarController extends Controller
{
    public function show(Request $request, User $user): BinaryFileResponse
    {
        $avatar = $user->avatar();

        abort_unless($avatar, 404);

        return response()->file($avatar->getPath());
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $request->user()->setAvatar($request->file('avatar'));

        return back()->with('status', 'avatar-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->avatar()?->delete();

        return back()->with('status', 'avatar-removed');
    }
}
