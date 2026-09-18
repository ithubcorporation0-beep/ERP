<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($invoices->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('There are no invoices currently eligible for a payment (an invoice must be SENT or PARTIALLY_PAID with a balance due).') }}</p>
                @else
                    <form method="POST" action="{{ route('payments.store') }}">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <x-input-label for="invoice_id" value="Invoice" />
                                <select id="invoice_id" name="invoice_id" onchange="updatePaymentBalance(this)"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">{{ __('Select an invoice…') }}</option>
                                    @foreach ($invoices as $invoice)
                                        <option value="{{ $invoice->id }}" data-balance="{{ $invoice->balance_due }}" data-currency="{{ $invoice->currency }}"
                                            @selected(old('invoice_id', $selectedInvoiceId) == $invoice->id)>
                                            {{ $invoice->number }} — {{ $invoice->customer->name }} ({{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }} due)
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('invoice_id')" class="mt-2" />
                                <p id="balance_display" class="mt-2 text-sm text-gray-600"></p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="amount" value="Amount" />
                                    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                                        :value="old('amount')" required />
                                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="currency" value="Currency" />
                                    <x-text-input id="currency" name="currency" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                                        :value="old('currency', $defaultCurrency)" required />
                                    <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="method" value="Method" />
                                    <select id="method" name="method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        @foreach ($methods as $option)
                                            <option value="{{ $option->value }}" @selected(old('method') === $option->value)>
                                                {{ $option->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('method')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="received_date" value="Received Date" />
                                    <x-text-input id="received_date" name="received_date" type="date" class="mt-1 block w-full"
                                        :value="old('received_date', now()->format('Y-m-d'))" required />
                                    <x-input-error :messages="$errors->get('received_date')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="reference" value="Reference (optional)" />
                                <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" />
                                <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="notes" value="Notes" />
                                <textarea id="notes" name="notes" rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button>{{ __('Record Payment') }}</x-primary-button>
                            <a href="{{ route('payments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Cancel') }}</a>
                        </div>
                    </form>

                    <script>
                        function updatePaymentBalance(select) {
                            const option = select.options[select.selectedIndex];
                            const display = document.getElementById('balance_display');
                            const amountInput = document.getElementById('amount');

                            if (option && option.value) {
                                const balance = parseFloat(option.dataset.balance);
                                display.textContent = 'Balance due: ' + option.dataset.currency + ' ' + balance.toFixed(2);
                                amountInput.setAttribute('max', balance);
                            } else {
                                display.textContent = '';
                                amountInput.removeAttribute('max');
                            }
                        }

                        document.addEventListener('DOMContentLoaded', function () {
                            const select = document.getElementById('invoice_id');
                            if (select.value) {
                                updatePaymentBalance(select);
                            }
                        });
                    </script>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
