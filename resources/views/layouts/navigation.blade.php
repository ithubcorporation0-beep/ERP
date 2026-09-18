<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    @foreach (\App\Support\ErpNavigation::items() as $item)
                        {{--
                            Items with a 'gate' key (e.g. Users, Settings) only render for users
                            who pass that Gate. The inverse check reads:
                                @cannot($item['gate']) ... show a disabled/locked state ... @endcannot
                        --}}
                        @if (isset($item['gate']))
                            @can($item['gate'], $item['gate_arg'] ?? null)
                                <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                                    {{ __($item['label']) }}
                                </x-nav-link>
                            @endcan
                        @else
                            <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                                {{ __($item['label']) }}
                            </x-nav-link>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                @php
                    $unreadNotifications = auth()->user()->unreadNotifications()->latest()->take(5)->get();
                    $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
                @endphp

                <x-dropdown align="right" width="80">
                    <x-slot name="trigger">
                        <button class="relative inline-flex items-center p-2 rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <span class="sr-only">{{ __('Notifications') }}</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @if ($unreadNotificationsCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full text-[10px] font-semibold text-white bg-red-500">
                                    {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                                </span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 flex items-center justify-between border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-700">{{ __('Notifications') }}</span>
                            @if ($unreadNotificationsCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900">{{ __('Mark all read') }}</button>
                                </form>
                            @endif
                        </div>

                        @forelse ($unreadNotifications as $notification)
                            <a href="{{ $notification->data['url'] ?? route('notifications.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 whitespace-normal">
                                {{ $notification->data['message'] ?? '' }}
                                <span class="block text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-2 text-sm text-gray-500">{{ __('No unread notifications.') }}</p>
                        @endforelse

                        <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-sm text-indigo-600 hover:bg-gray-100 border-t border-gray-100">
                            {{ __('View all') }}
                        </a>
                    </x-slot>
                </x-dropdown>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach (\App\Support\ErpNavigation::items() as $item)
                @if (isset($item['gate']))
                    @can($item['gate'], $item['gate_arg'] ?? null)
                        <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                            {{ __($item['label']) }}
                        </x-responsive-nav-link>
                    @endcan
                @else
                    <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                        {{ __($item['label']) }}
                    </x-responsive-nav-link>
                @endif
            @endforeach

            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')">
                {{ __('Notifications') }}
                @if ($unreadNotificationsCount > 0)
                    <span class="ms-1 inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full text-[10px] font-semibold text-white bg-red-500">
                        {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                    </span>
                @endif
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
