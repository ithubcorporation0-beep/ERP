@props(['label', 'value'])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg p-6']) }}>
    <dt class="text-sm font-medium text-gray-500 truncate">{{ $label }}</dt>
    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $value }}</dd>
</div>
