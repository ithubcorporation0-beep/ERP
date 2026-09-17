<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $project->name }}
            </h2>

            <div class="flex items-center gap-4">
                @can('create', \App\Models\Task::class)
                    <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('New Task') }}
                    </a>
                @endcan
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('Edit') }}
                    </a>
                @endcan
                <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Back to Projects') }}</a>
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
                        <dt class="text-sm text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="text-sm text-gray-900">
                            @can('view', $project->customer)
                                <a href="{{ route('customers.show', $project->customer) }}" class="hover:underline">{{ $project->customer->name }}</a>
                            @else
                                {{ $project->customer->name }}
                            @endcan
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Code') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Status') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Due Date') }}</dt>
                        <dd class="text-sm text-gray-900">{{ optional($project->due_date)->format('Y-m-d') ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($project->description)
                    <div class="mt-4">
                        <dt class="text-sm text-gray-500">{{ __('Description') }}</dt>
                        <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $project->description }}</dd>
                    </div>
                @endif
            </div>

            {{-- Members --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Members') }}</h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('User') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($project->users as $member)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $member->name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">
                                    @can('manageMembers', $project)
                                        <form method="POST" action="{{ route('projects.members.update', [$project, $member->pivot->id]) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" onchange="this.form.submit()" class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                @foreach ($memberRoles as $option)
                                                    <option value="{{ $option->value }}" @selected($member->pivot->role === $option)>
                                                        {{ $option->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        {{ $member->pivot->role->label() }}
                                    @endcan
                                </td>
                                <td class="px-3 py-2 text-right text-sm">
                                    @can('manageMembers', $project)
                                        <form method="POST" action="{{ route('projects.members.destroy', [$project, $member->pivot->id]) }}"
                                            onsubmit="return confirm('{{ __('Remove this member?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-sm text-gray-500">{{ __('No members yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @can('manageMembers', $project)
                    <form method="POST" action="{{ route('projects.members.store', $project) }}" class="mt-6 flex flex-wrap items-end gap-4">
                        @csrf
                        <div>
                            <x-input-label for="user_id" value="Add member" />
                            <select id="user_id" name="user_id" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">{{ __('Select a user…') }}</option>
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role" value="Role" />
                            <select id="role" name="role" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach ($memberRoles as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">{{ __('Add') }}</x-primary-button>
                    </form>
                @endcan
            </div>

            {{-- Tasks --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Tasks') }}</h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Title') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Priority') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Due') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($project->tasks as $task)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900">
                                    <a href="{{ route('tasks.show', $task) }}" class="hover:underline">{{ $task->title }}</a>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $task->status->label() }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $task->priority->label() }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ optional($task->due_date)->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-sm text-gray-500">{{ __('No tasks yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
