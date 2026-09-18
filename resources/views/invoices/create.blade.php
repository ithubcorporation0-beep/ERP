<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf

                    @include('invoices._form')

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Create Invoice') }}</x-primary-button>
                        <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
