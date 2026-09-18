@php
    $addressLines = fn (?array $address) => $address
        ? collect([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            trim(($address['city'] ?? '').(isset($address['state']) ? ', '.$address['state'] : '')),
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ])->filter()
        : collect();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $customer->name }}
            </h2>

            <div class="flex items-center gap-4">
                @can('update', $customer)
                    <a href="{{ route('customers.edit', $customer) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('Edit') }}
                    </a>
                @endcan
                <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Back to Customers') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Details') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Email') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Phone') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Status') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->status->label() }}</dd>
                    </div>
                </dl>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Billing Address') }}</h3>
                    @php $lines = $addressLines($customer->billing_address); @endphp
                    @if ($lines->isNotEmpty())
                        <p class="text-sm text-gray-900">{!! $lines->map(fn ($line) => e($line))->implode('<br>') !!}</p>
                    @else
                        <p class="text-sm text-gray-500">{{ __('No billing address on file.') }}</p>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Shipping Address') }}</h3>
                    @php $lines = $addressLines($customer->shipping_address); @endphp
                    @if ($lines->isNotEmpty())
                        <p class="text-sm text-gray-900">{!! $lines->map(fn ($line) => e($line))->implode('<br>') !!}</p>
                    @else
                        <p class="text-sm text-gray-500">{{ __('No shipping address on file.') }}</p>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Notes') }}</h3>
                <p class="text-sm text-gray-900 whitespace-pre-line">{{ $customer->notes ?? __('No notes.') }}</p>
            </div>

            @include('documents._panel', ['model' => $customer, 'type' => 'customers'])
        </div>
    </div>
</x-app-layout>
