@php
    $selectedRoles = old('roles', $selectedRoles ?? []);
    $selectedCustomerId = old('customer_id', $selectedCustomerId ?? null);
@endphp

<div x-data="{ roles: {{ json_encode($selectedRoles) }} }">
    <x-input-label value="Roles" />
    <div class="mt-1 grid grid-cols-2 sm:grid-cols-3 gap-2">
        @foreach ($roles as $option)
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="roles[]" value="{{ $option }}" x-model="roles"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @if ($option === \App\Support\Roles::SUPER_ADMIN && isset($lockSuperAdmin) && $lockSuperAdmin) disabled checked @endif>
                {{ $option }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
    <x-input-error :messages="$errors->get('roles.*')" class="mt-2" />

    @if (isset($lockSuperAdmin) && $lockSuperAdmin)
        <input type="hidden" name="roles[]" value="{{ \App\Support\Roles::SUPER_ADMIN }}">
        <p class="mt-1 text-xs text-gray-500">{{ __('The SUPER_ADMIN role cannot be removed from this user.') }}</p>
    @endif

    <div class="mt-4" x-show="roles.includes('{{ \App\Support\Roles::CLIENT }}')">
        <x-input-label for="customer_id" value="Customer (required for CLIENT)" />
        <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">{{ __('Select a customer…') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) $selectedCustomerId === $customer->id)>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
    </div>
</div>
