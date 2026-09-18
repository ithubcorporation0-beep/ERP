<div class="space-y-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="customer_id" value="Customer" />
            <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="">{{ __('Select a customer…') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((int) old('customer_id', $invoice->customer_id) === $customer->id)>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="project_id" value="Project (optional)" />
            <select id="project_id" name="project_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="">{{ __('None') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((int) old('project_id', $invoice->project_id) === $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div>
            <x-input-label for="issue_date" value="Issue Date" />
            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full"
                :value="old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
            <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="due_date" value="Due Date" />
            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full"
                :value="old('due_date', optional($invoice->due_date)->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="currency" value="Currency" />
            <x-text-input id="currency" name="currency" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                :value="old('currency', $invoice->currency ?? 'USD')" required />
            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div>
            <x-input-label for="discount_type" value="Discount Type" />
            <select id="discount_type" name="discount_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                @foreach ($discountTypes as $option)
                    <option value="{{ $option->value }}" @selected(old('discount_type', $invoice->discount_type?->value ?? 'NONE') === $option->value)>
                        {{ $option->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('discount_type')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="discount_value" value="Discount Value" />
            <x-text-input id="discount_value" name="discount_value" type="number" step="0.01" min="0" class="mt-1 block w-full"
                :value="old('discount_value', $invoice->discount_value ?? 0)" required />
            <x-input-error :messages="$errors->get('discount_value')" class="mt-2" />
        </div>

        @if ($invoice->exists)
            <div>
                <x-input-label for="amount_paid" value="Amount Paid" />
                <x-text-input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" class="mt-1 block w-full"
                    :value="old('amount_paid', $invoice->amount_paid)" required />
                <x-input-error :messages="$errors->get('amount_paid')" class="mt-2" />
            </div>
        @endif
    </div>

    <div>
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="4"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $invoice->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
