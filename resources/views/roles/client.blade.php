<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Client Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p>{{ __('Welcome back, :name!', ['name' => Auth::user()->name]) }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ __('This area is restricted to the CLIENT role.') }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="My Open Invoices" :value="$myOpenInvoices" />
                <x-stat-card label="My Balance Due" :value="number_format($myBalanceDue, 2)" />
                <x-stat-card label="My Active Projects" :value="$myActiveProjects" />
            </dl>
        </div>
    </div>
</x-app-layout>
