<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Expenses') }}
            </h2>

            <div class="flex items-center gap-4">
                <a href="{{ route('expenses.report', request()->query()) }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Report') }}
                </a>

                @can('create', \App\Models\Expense::class)
                    <a href="{{ route('expenses.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('New Expense') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="category" value="Category" />
                        <select id="category" name="category" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($categories as $option)
                                <option value="{{ $option->value }}" @selected($category === $option->value)>
                                    {{ $option->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="project_id" value="Project" />
                        <select id="project_id" name="project_id" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected($projectId === $project->id)>
                                    {{ $project->name }}
                                </option>
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

                    <a href="{{ route('expenses.export', request()->query()) }}" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ __('Export CSV') }}
                    </a>

                    @if ($category || $projectId || $dateFrom || $dateTo)
                        <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Category') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Customer') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Amount') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($expenses as $expense)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('expenses.show', $expense) }}" class="hover:underline">{{ $expense->expense_date->format('Y-m-d') }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $expense->category->label() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $expense->customer?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $expense->project?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $expense->currency }} {{ number_format($expense->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                    @can('update', $expense)
                                        <a href="{{ route('expenses.edit', $expense) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                    @endcan

                                    @can('delete', $expense)
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline"
                                            onsubmit="return confirm('{{ __('Delete this expense?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('No expenses found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $expenses->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
