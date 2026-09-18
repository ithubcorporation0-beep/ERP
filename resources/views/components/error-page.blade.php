@props(['code', 'title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $code }} — {{ $title }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center px-4">
            <div class="max-w-md w-full bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <p class="text-6xl font-bold text-gray-300">{{ $code }}</p>
                <h1 class="mt-4 text-xl font-semibold text-gray-800">{{ $title }}</h1>
                <p class="mt-2 text-sm text-gray-500">{{ $slot }}</p>

                <a href="{{ auth()->check() ? route(\App\Support\Roles::dashboardRouteFor(auth()->user())) : route('login') }}"
                    class="mt-6 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    {{ auth()->check() ? __('Back to Dashboard') : __('Back to Login') }}
                </a>
            </div>
        </div>
    </body>
</html>
