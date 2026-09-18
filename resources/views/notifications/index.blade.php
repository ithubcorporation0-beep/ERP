<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifications') }}
            </h2>

            @if ($notifications->contains(fn ($notification) => $notification->unread()))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Mark all read') }}</x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-100">
                    @forelse ($notifications as $notification)
                        <li class="px-6 py-4 flex items-start justify-between gap-4 {{ $notification->unread() ? 'bg-indigo-50/40' : '' }}">
                            <div>
                                <a href="{{ $notification->data['url'] ?? '#' }}" class="text-sm text-gray-900 hover:underline">
                                    {{ $notification->data['message'] ?? '' }}
                                </a>
                                <p class="mt-1 text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>

                            @if ($notification->unread())
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900 whitespace-nowrap">{{ __('Mark as read') }}</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ __('Read') }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="px-6 py-4 text-sm text-gray-500">{{ __('No notifications yet.') }}</li>
                    @endforelse
                </ul>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
