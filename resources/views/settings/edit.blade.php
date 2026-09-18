<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Company Logo') }}</h3>

                <div class="flex items-center gap-6">
                    <img src="{{ route('settings.logo.show') }}" alt="{{ __('Company logo') }}"
                        class="h-16 w-16 rounded-md object-contain bg-gray-50 border border-gray-200" onerror="this.style.visibility='hidden'">

                    <form method="POST" action="{{ route('settings.logo.store') }}" enctype="multipart/form-data" class="flex items-center gap-3">
                        @csrf
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required
                            class="block text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:bg-gray-800 file:text-white hover:file:bg-gray-700">
                        <x-secondary-button type="submit">{{ __('Upload') }}</x-secondary-button>
                    </form>

                    @if ($logo)
                        <form method="POST" action="{{ route('settings.logo.destroy') }}"
                            onsubmit="return confirm('{{ __('Remove the company logo?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
                        </form>
                    @endif
                </div>

                <x-input-error :messages="$errors->get('logo')" class="mt-2" />
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('General') }}</h3>

                <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="company_name" value="Company Name" />
                        <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $companyName)" />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="invoice_prefix" value="Invoice Prefix" />
                            <x-text-input id="invoice_prefix" name="invoice_prefix" type="text" maxlength="20" class="mt-1 block w-full uppercase"
                                :value="old('invoice_prefix', $invoicePrefix)" required />
                            <p class="mt-1 text-xs text-gray-500">{{ __('e.g. "INV" produces numbers like INV-2026-000001.') }}</p>
                            <x-input-error :messages="$errors->get('invoice_prefix')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="invoice_numbering_reset" value="Invoice Numbering Reset" />
                            <select id="invoice_numbering_reset" name="invoice_numbering_reset" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach ($numberingResetOptions as $option)
                                    <option value="{{ $option }}" @selected(old('invoice_numbering_reset', $numberingReset) === $option)>
                                        {{ ucfirst($option) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">{{ __('Yearly resets the sequence (and shows the year) each January; continuous never resets.') }}</p>
                            <x-input-error :messages="$errors->get('invoice_numbering_reset')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="default_currency" value="Default Currency" />
                            <x-text-input id="default_currency" name="default_currency" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                                :value="old('default_currency', $defaultCurrency)" required />
                            <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="default_tax_behavior" value="Default Tax Behavior" />
                            <select id="default_tax_behavior" name="default_tax_behavior" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach ($taxBehaviorOptions as $option)
                                    <option value="{{ $option }}" @selected(old('default_tax_behavior', $taxBehavior) === $option)>
                                        {{ ucfirst(strtolower(str_replace('_', ' ', $option))) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('default_tax_behavior')" class="mt-2" />
                        </div>
                    </div>

                    <x-primary-button>{{ __('Save Settings') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
