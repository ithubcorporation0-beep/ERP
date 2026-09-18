<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Payment') }} #{{ $payment->id }}
            </h2>

            <div class="flex items-center gap-4">
                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ __('View Invoice') }} {{ $payment->invoice->number }}
                </a>
                <a href="{{ route('payments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Details') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @can('view', $payment->invoice->customer)
                                <a href="{{ route('customers.show', $payment->invoice->customer) }}" class="hover:underline">{{ $payment->customer->name }}</a>
                            @else
                                {{ $payment->customer->name }}
                            @endcan
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Invoice Total') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $payment->invoice->currency }} {{ number_format($payment->invoice->total, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Invoice Balance Due') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $payment->invoice->currency }} {{ number_format($payment->invoice->balance_due, 2) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Payment') }}</h3>

                @can('update', $payment)
                    <form method="POST" action="{{ route('payments.update', $payment) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="amount" value="Amount" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                                    :value="old('amount', $payment->amount)" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="currency" value="Currency" />
                                <x-text-input id="currency" name="currency" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                                    :value="old('currency', $payment->currency)" required />
                                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="method" value="Method" />
                                <select id="method" name="method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @foreach ($methods as $option)
                                        <option value="{{ $option->value }}" @selected(old('method', $payment->method->value) === $option->value)>
                                            {{ $option->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('method')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="received_date" value="Received Date" />
                                <x-text-input id="received_date" name="received_date" type="date" class="mt-1 block w-full"
                                    :value="old('received_date', optional($payment->received_date)->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('received_date')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="reference" value="Reference (optional)" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference', $payment->reference)" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" name="notes" rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $payment->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button type="submit">{{ __('Save Changes') }}</x-primary-button>

                            @can('delete', $payment)
                                <button type="button" class="text-sm text-red-600 hover:text-red-900"
                                    onclick="if (confirm('{{ __('Delete this payment?') }}')) { document.getElementById('delete-payment-form').submit(); }">
                                    {{ __('Delete Payment') }}
                                </button>
                            @endcan
                        </div>
                    </form>

                    @can('delete', $payment)
                        <form id="delete-payment-form" method="POST" action="{{ route('payments.destroy', $payment) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endcan
                @else
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm text-gray-500">{{ __('Amount') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">{{ __('Method') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $payment->method->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">{{ __('Received Date') }}</dt>
                            <dd class="text-sm text-gray-900">{{ $payment->received_date->format('Y-m-d') }}</dd>
                        </div>
                        @if ($payment->reference)
                            <div>
                                <dt class="text-sm text-gray-500">{{ __('Reference') }}</dt>
                                <dd class="text-sm text-gray-900">{{ $payment->reference }}</dd>
                            </div>
                        @endif
                        @if ($payment->notes)
                            <div class="sm:col-span-3">
                                <dt class="text-sm text-gray-500">{{ __('Notes') }}</dt>
                                <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $payment->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
