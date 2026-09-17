<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $task->title }}
            </h2>

            <div class="flex items-center gap-4">
                @can('update', $task)
                    <a href="{{ route('tasks.edit', $task) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('Edit') }}
                    </a>
                @endcan
                <a href="{{ route('tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Back to Tasks') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Details') }}</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Project') }}</dt>
                        <dd class="text-sm text-gray-900">
                            <a href="{{ route('projects.show', $task->project) }}" class="hover:underline">{{ $task->project->name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Status') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @can('updateStatus', $task)
                                <form method="POST" action="{{ route('tasks.status.update', $task) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        @foreach ($statuses as $option)
                                            <option value="{{ $option->value }}" @selected($task->status === $option)>
                                                {{ $option->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                {{ $task->status->label() }}
                            @endcan
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Priority') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $task->priority->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Due Date') }}</dt>
                        <dd class="text-sm text-gray-900">{{ optional($task->due_date)->format('Y-m-d') ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($task->description)
                    <div class="mt-4">
                        <dt class="text-sm text-gray-500">{{ __('Description') }}</dt>
                        <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $task->description }}</dd>
                    </div>
                @endif
            </div>

            {{-- Assignees --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Assignees') }}</h3>

                <ul class="divide-y divide-gray-100">
                    @forelse ($task->assignees as $assignee)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-900">{{ $assignee->name }}</span>

                            @can('manageAssignees', $task)
                                <form method="POST" action="{{ route('tasks.assignees.destroy', [$task, $assignee]) }}"
                                    onsubmit="return confirm('{{ __('Remove this assignee?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-500">{{ __('No assignees yet.') }}</li>
                    @endforelse
                </ul>

                @can('manageAssignees', $task)
                    <form method="POST" action="{{ route('tasks.assignees.store', $task) }}" class="mt-6 flex flex-wrap items-end gap-4">
                        @csrf
                        <div>
                            <x-input-label for="user_id" value="Add assignee" />
                            <select id="user_id" name="user_id" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">{{ __('Select a user…') }}</option>
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">{{ __('Add') }}</x-primary-button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
