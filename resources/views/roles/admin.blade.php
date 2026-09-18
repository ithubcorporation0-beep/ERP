<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p>{{ __('Welcome back, :name!', ['name' => Auth::user()->name]) }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ __('This area is restricted to the ADMIN and SUPER_ADMIN roles.') }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-stat-card label="Total Customers" :value="$totalCustomers" />
                <x-stat-card label="Active Projects" :value="$activeProjects" />
                <x-stat-card label="Open Invoices" :value="$openInvoices" />
                <x-stat-card label="Balance Due" :value="number_format($balanceDue, 2)" />
                <x-stat-card label="Total Users" :value="$totalUsers" />
            </dl>
        </div>
    </div>
</x-app-layout>
