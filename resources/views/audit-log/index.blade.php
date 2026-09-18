@php
    $entityLabels = collect($entityTypes)->flip();

    $flatten = function (array $properties) {
        $lines = [];

        foreach ($properties as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $lines[] = $key.'.'.$subKey.': '.(is_scalar($subValue) || is_null($subValue) ? ($subValue ?? '—') : json_encode($subValue));
                }
            } else {
                $lines[] = $key.': '.(is_scalar($value) || is_null($value) ? ($value ?? '—') : json_encode($value));
            }
        }

        return $lines;
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Audit Log') }}
            </h2>

            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Users') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('audit-log.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="subject_type" value="Entity" />
                        <select id="subject_type" name="subject_type" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($entityTypes as $label => $class)
                                <option value="{{ $class }}" @selected($subjectType === $class)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="causer_id" value="Actor" />
                        <select id="causer_id" name="causer_id" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($actors as $actor)
                                <option value="{{ $actor->id }}" @selected($causerId === $actor->id)>{{ $actor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="event" value="Action" />
                        <select id="event" name="event" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($events as $option)
                                <option value="{{ $option }}" @selected($event === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="date_from" value="From" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1" :value="$dateFrom" />
                    </div>

                    <div>
                        <x-input-label for="date_to" value="To" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1" :value="$dateTo" />
                    </div>

                    <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>

                    @if ($subjectType || $causerId || $event || $dateFrom || $dateTo)
                        <a href="{{ route('audit-log.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Timestamp') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actor') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Entity') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Changed Fields') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($activities as $activity)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $activity->causer?->name ?? __('System') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span @class([
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        'bg-green-100 text-green-800' => $activity->event === 'created',
                                        'bg-blue-100 text-blue-800' => $activity->event === 'updated',
                                        'bg-red-100 text-red-800' => $activity->event === 'deleted',
                                        'bg-gray-100 text-gray-800' => ! in_array($activity->event, ['created', 'updated', 'deleted']),
                                    ])>
                                        {{ ucfirst($activity->event ?? 'unknown') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $entityLabels[$activity->subject_type] ?? class_basename($activity->subject_type) }}
                                    #{{ $activity->subject_id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @php $lines = $flatten($activity->properties?->toArray() ?? []); @endphp
                                    @if (count($lines))
                                        <ul class="space-y-0.5">
                                            @foreach ($lines as $line)
                                                <li class="whitespace-nowrap">{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('No activity recorded yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $activities->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
