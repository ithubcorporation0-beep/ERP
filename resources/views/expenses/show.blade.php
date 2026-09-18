<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Expense') }} #{{ $expense->id }}
            </h2>

            <div class="flex items-center gap-4">
                @can('update', $expense)
                    <a href="{{ route('expenses.edit', $expense) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('Edit') }}
                    </a>
                @endcan
                <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Back to Expenses') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Details') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Category') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $expense->category->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Amount') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $expense->currency }} {{ number_format($expense->amount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Date') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $expense->expense_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @if ($expense->customer)
                                @can('view', $expense->customer)
                                    <a href="{{ route('customers.show', $expense->customer) }}" class="hover:underline">{{ $expense->customer->name }}</a>
                                @else
                                    {{ $expense->customer->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Project') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @if ($expense->project)
                                @can('view', $expense->project)
                                    <a href="{{ route('projects.show', $expense->project) }}" class="hover:underline">{{ $expense->project->name }}</a>
                                @else
                                    {{ $expense->project->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($expense->notes)
                    <div class="mt-4">
                        <dt class="text-sm text-gray-500">{{ __('Notes') }}</dt>
                        <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $expense->notes }}</dd>
                    </div>
                @endif
            </div>

            @include('documents._panel', ['model' => $expense, 'type' => 'expenses'])
        </div>
    </div>
</x-app-layout>
