<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Avatars live on the private disk like every other upload in this app, so
 * viewing one — even your own — goes through this authenticated route
 * rather than a public URL.
 */
class AvatarController extends Controller
{
    public function show(Request $request, User $user): StreamedResponse
    {
        $avatar = $user->avatar();

        abort_unless($avatar, 404);

        // Storage::disk(...)->response() streams via Flysystem rather than
        // assuming a real local path, so this works the same whether the
        // 'private' disk is local (Sail/dev) or s3 (e.g. Cloudflare R2).
        return Storage::disk($avatar->disk)->response($avatar->getPathRelativeToRoot());
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
