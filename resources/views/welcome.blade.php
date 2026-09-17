<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'ERP') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-6">
            <div class="w-full max-w-md text-center">
                <h1 class="text-3xl font-semibold text-gray-800">{{ config('app.name', 'ERP') }}</h1>
                <p class="mt-2 text-gray-500">Sign in to manage customers, projects, invoices, and more.</p>

                <div class="mt-8 flex justify-center gap-4">
                    <a
                        href="{{ route('login') }}"
                        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        {{ __('Log in') }}
                    </a>

                    @if (Route::has('register'))
                        <a
                            href="{{ route('register') }}"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            {{ __('Register') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
