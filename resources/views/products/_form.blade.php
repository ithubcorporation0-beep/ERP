<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $product->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sku" value="SKU" />
        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full"
            :value="old('sku', $product->sku)" />
        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="unit_price" value="Unit Price" />
            <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full"
                :value="old('unit_price', $product->unit_price)" required />
            <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="tax_rate" value="Tax Rate (%)" />
            <x-text-input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full"
                :value="old('tax_rate', $product->tax_rate)" />
            <x-input-error :messages="$errors->get('tax_rate')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="4"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
