@php
    $billing = old('billing_address', $customer->billing_address ?? []);
    $shipping = old('shipping_address', $customer->shipping_address ?? []);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $customer->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                :value="old('email', $customer->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                :value="old('phone', $customer->phone)" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach ($statuses as $option)
                <option value="{{ $option->value }}" @selected(old('status', $customer->status?->value) === $option->value)>
                    {{ $option->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    @foreach (['billing_address' => ['label' => 'Billing Address', 'value' => $billing], 'shipping_address' => ['label' => 'Shipping Address', 'value' => $shipping]] as $field => $meta)
        <fieldset class="border border-gray-200 rounded-md p-4">
            <legend class="px-2 text-sm font-medium text-gray-700">{{ $meta['label'] }}</legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label :for="\"{$field}_line1\"" value="Address Line 1" />
                    <x-text-input :id="\"{$field}_line1\"" :name="\"{$field}[line1]\"" type="text" class="mt-1 block w-full"
                        :value="$meta['value']['line1'] ?? ''" />
                    <x-input-error :messages="$errors->get(\"{$field}.line1\")" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label :for="\"{$field}_line2\"" value="Address Line 2" />
                    <x-text-input :id="\"{$field}_line2\"" :name="\"{$field}[line2]\"" type="text" class="mt-1 block w-full"
                        :value="$meta['value']['line2'] ?? ''" />
                    <x-input-error :messages="$errors->get(\"{$field}.line2\")" class="mt-2" />
                </div>

                <div>
                    <x-input-label :for="\"{$field}_city\"" value="City" />
                    <x-text-input :id="\"{$field}_city\"" :name="\"{$field}[city]\"" type="text" class="mt-1 block w-full"
                        :value="$meta['value']['city'] ?? ''" />
                    <x-input-error :messages="$errors->get(\"{$field}.city\")" class="mt-2" />
                </div>

                <div>
                    <x-input-label :for="\"{$field}_state\"" value="State / Province" />
                    <x-text-input :id="\"{$field}_state\"" :name="\"{$field}[state]\"" type="text" class="mt-1 block w-full"
                        :value="$meta['value']['state'] ?? ''" />
                    <x-input-error :messages="$errors->get(\"{$field}.state\")" class="mt-2" />
                </div>

                <div>
                    <x-input-label :for="\"{$field}_postal_code\"" value="Postal Code" />
                    <x-text-input :id="\"{$field}_postal_code\"" :name="\"{$field}[postal_code]\"" type="text" class="mt-1 block w-full"
                        :value="$meta['value']['postal_code'] ?? ''" />
                    <x-input-error :messages="$errors->get(\"{$field}.postal_code\")" class="mt-2" />
                </div>

                <div>
                    <x-input-label :for="\"{$field}_country\"" value="Country" />
                    <x-text-input :id="\"{$field}_country\"" :name="\"{$field}[country]\"" type="text" class="mt-1 block w-full"
                        :value="$meta['value']['country'] ?? ''" />
                    <x-input-error :messages="$errors->get(\"{$field}.country\")" class="mt-2" />
                </div>
            </div>
        </fieldset>
    @endforeach

    <div>
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="4"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $customer->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
