<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $invoice->number }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status->badgeClasses() }}">
                    {{ $invoice->status->label() }}
                </span>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('invoices.print', $invoice) }}" target="_blank"
                    class="text-sm text-gray-500 hover:text-gray-700">{{ __('Print') }}</a>

                @can('markSent', $invoice)
                    <form method="POST" action="{{ route('invoices.mark-sent', $invoice) }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Mark Sent') }}</x-secondary-button>
                    </form>
                @endcan

                @can('markVoid', $invoice)
                    <form method="POST" action="{{ route('invoices.mark-void', $invoice) }}"
                        onsubmit="return confirm('{{ __('Void this invoice? This cannot be undone.') }}');">
                        @csrf
                        <x-danger-button type="submit">{{ __('Void') }}</x-danger-button>
                    </form>
                @endcan

                @can('update', $invoice)
                    <a href="{{ route('invoices.edit', $invoice) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('Edit') }}
                    </a>
                @endcan

                <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            @foreach ($errors->all() as $error)
                <div class="bg-red-50 text-red-700 text-sm rounded-md p-4">{{ $error }}</div>
            @endforeach

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Details') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @can('view', $invoice->customer)
                                <a href="{{ route('customers.show', $invoice->customer) }}" class="hover:underline">{{ $invoice->customer->name }}</a>
                            @else
                                {{ $invoice->customer->name }}
                            @endcan
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Project') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $invoice->project?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Issue Date') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $invoice->issue_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Due Date') }}</dt>
                        <dd class="text-sm text-gray-900">{{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($invoice->notes)
                    <div class="mt-4">
                        <dt class="text-sm text-gray-500">{{ __('Notes') }}</dt>
                        <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $invoice->notes }}</dd>
                    </div>
                @endif
            </div>

            {{-- Line items --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Line Items') }}</h3>

                @can('manageItems', $invoice)
                    @foreach ($invoice->items as $item)
                        <form id="item-{{ $item->id }}-form" method="POST" action="{{ route('invoices.items.update', [$invoice, $item]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="item_type" value="{{ $item->item_type->value }}">
                            <input type="hidden" name="ref_id" value="{{ $item->ref_id }}">
                        </form>
                    @endforeach
                @endcan

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Description') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Qty') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Unit Price') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Tax %') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Line Total') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoice->items as $item)
                            <tr>
                                @can('manageItems', $invoice)
                                    <td class="px-3 py-2">
                                        <input form="item-{{ $item->id }}-form" name="description" value="{{ $item->description }}"
                                            class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input form="item-{{ $item->id }}-form" name="quantity" type="number" step="0.001" min="0.001" value="{{ $item->quantity }}"
                                            class="w-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input form="item-{{ $item->id }}-form" name="unit_price" type="number" step="0.01" min="0" value="{{ $item->unit_price }}"
                                            class="w-24 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input form="item-{{ $item->id }}-form" name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ $item->tax_rate }}"
                                            class="w-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-900">{{ number_format($item->line_total, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-sm space-x-2 whitespace-nowrap">
                                        <button form="item-{{ $item->id }}-form" type="submit" class="text-indigo-600 hover:text-indigo-900">{{ __('Save') }}</button>
                                        <form method="POST" action="{{ route('invoices.items.destroy', [$invoice, $item]) }}" class="inline"
                                            onsubmit="return confirm('{{ __('Remove this line item?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
                                        </form>
                                    </td>
                                @else
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $item->tax_rate !== null ? number_format($item->tax_rate, 2).'%' : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-900">{{ number_format($item->line_total, 2) }}</td>
                                    <td></td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-2 text-sm text-gray-500">{{ __('No line items yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @can('manageItems', $invoice)
                    <form method="POST" action="{{ route('invoices.items.store', $invoice) }}" class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                        @csrf

                        <div>
                            <x-input-label for="catalog_picker" value="Add from catalog (optional)" />
                            <select id="catalog_picker" onchange="fillInvoiceItemFromCatalog(this)"
                                class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('Custom line') }}</option>
                                <optgroup label="{{ __('Services') }}">
                                    @foreach ($services as $service)
                                        <option value="SERVICE:{{ $service->id }}" data-description="{{ $service->name }}" data-price="{{ $service->unit_price }}" data-tax="{{ $service->tax_rate }}">
                                            {{ $service->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="{{ __('Products') }}">
                                    @foreach ($products as $product)
                                        <option value="PRODUCT:{{ $product->id }}" data-description="{{ $product->name }}" data-price="{{ $product->unit_price }}" data-tax="{{ $product->tax_rate }}">
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        <input type="hidden" name="item_type" id="new_item_type" value="TEXT">
                        <input type="hidden" name="ref_id" id="new_ref_id" value="">

                        <div class="flex flex-wrap items-end gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <x-input-label for="new_description" value="Description" />
                                <x-text-input id="new_description" name="description" type="text" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="new_quantity" value="Qty" />
                                <x-text-input id="new_quantity" name="quantity" type="number" step="0.001" min="0.001" value="1" class="mt-1 w-24" required />
                                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="new_unit_price" value="Unit Price" />
                                <x-text-input id="new_unit_price" name="unit_price" type="number" step="0.01" min="0" value="0" class="mt-1 w-28" required />
                                <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="new_tax_rate" value="Tax %" />
                                <x-text-input id="new_tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 w-24" />
                                <x-input-error :messages="$errors->get('tax_rate')" class="mt-2" />
                            </div>

                            <x-primary-button type="submit">{{ __('Add Line') }}</x-primary-button>
                        </div>
                    </form>

                    <script>
                        function fillInvoiceItemFromCatalog(select) {
                            const [type, id] = (select.value || '').split(':');
                            const option = select.options[select.selectedIndex];

                            document.getElementById('new_item_type').value = type || 'TEXT';
                            document.getElementById('new_ref_id').value = id || '';

                            if (type) {
                                document.getElementById('new_description').value = option.dataset.description || '';
                                document.getElementById('new_unit_price').value = option.dataset.price || 0;
                                document.getElementById('new_tax_rate').value = option.dataset.tax || '';
                            }
                        }
                    </script>
                @endcan
            </div>

            {{-- Totals --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Totals') }}</h3>
                <dl class="max-w-xs ml-auto space-y-2">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('Subtotal') }}</dt>
                        <dd class="text-gray-900">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">
                            {{ __('Discount') }}
                            @if ($invoice->discount_type !== \App\Enums\InvoiceDiscountType::NONE)
                                ({{ $invoice->discount_type->label() }}: {{ $invoice->discount_value }}{{ $invoice->discount_type === \App\Enums\InvoiceDiscountType::PERCENT ? '%' : '' }})
                            @endif
                        </dt>
                        <dd class="text-gray-900">
                            {{ $invoice->currency }} {{ number_format(max(0, $invoice->subtotal + $invoice->tax_total - $invoice->total), 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('Tax') }}</dt>
                        <dd class="text-gray-900">{{ $invoice->currency }} {{ number_format($invoice->tax_total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold border-t border-gray-200 pt-2">
                        <dt class="text-gray-900">{{ __('Total') }}</dt>
                        <dd class="text-gray-900">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('Amount Paid') }}</dt>
                        <dd class="text-gray-900">{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm font-semibold">
                        <dt class="text-gray-900">{{ __('Balance Due') }}</dt>
                        <dd class="text-gray-900">{{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Payments --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('Payments') }}</h3>

                    @can('create', \App\Models\Payment::class)
                        @if (in_array($invoice->status, [\App\Enums\InvoiceStatus::SENT, \App\Enums\InvoiceStatus::PARTIALLY_PAID], true) && $invoice->balance_due > 0)
                            <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                                {{ __('Record Payment') }}
                            </a>
                        @endif
                    @endcan
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Method') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Reference') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoice->payments as $payment)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900">
                                    <a href="{{ route('payments.show', $payment) }}" class="hover:underline">{{ $payment->received_date->format('Y-m-d') }}</a>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $payment->method->label() }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $payment->reference ?? '—' }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-900">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-sm text-gray-500">{{ __('No payments recorded yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
