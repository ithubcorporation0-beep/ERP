<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Avatar') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Upload a picture to personalize your account. JPG, PNG, or WEBP, up to 2MB.') }}
        </p>
    </header>

    <div class="mt-6 flex items-center gap-6">
        <img src="{{ route('users.avatar', $user) }}" alt="{{ __('Current avatar') }}"
            class="h-16 w-16 rounded-full object-cover bg-gray-100" onerror="this.style.visibility='hidden'">

        <form method="POST" action="{{ route('profile.avatar.store') }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" required
                class="block text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:bg-gray-800 file:text-white hover:file:bg-gray-700">
            <x-secondary-button type="submit">{{ __('Upload') }}</x-secondary-button>
        </form>

        @if ($user->avatar())
            <form method="POST" action="{{ route('profile.avatar.destroy') }}"
                onsubmit="return confirm('{{ __('Remove your avatar?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
            </form>
        @endif
    </div>

    <x-input-error :messages="$errors->get('avatar')" class="mt-2" />

    @if (session('status') === 'avatar-updated')
        <p class="mt-2 text-sm text-green-600">{{ __('Avatar updated.') }}</p>
    @elseif (session('status') === 'avatar-removed')
        <p class="mt-2 text-sm text-green-600">{{ __('Avatar removed.') }}</p>
    @endif
</section>
