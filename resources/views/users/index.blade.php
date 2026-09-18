<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Users') }}
            </h2>

            <div class="flex items-center gap-4">
                <a href="{{ route('audit-log.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ __('Audit Log') }}
                </a>

                @can('create', \App\Models\User::class)
                    <a href="{{ route('users.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('New User') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="bg-amber-50 text-amber-800 text-sm rounded-md p-4">
                    {{ session('warning') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="search" value="Search by name or email" />
                        <x-text-input id="search" name="search" type="text" class="mt-1" :value="$search" placeholder="Search users…"
                            x-on:input.debounce.400ms="$el.form.requestSubmit()" />
                    </div>

                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($roles as $option)
                                <option value="{{ $option }}" @selected($role === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>

                    @if ($search || $role || $status)
                        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('User') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Email') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Roles') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ route('users.avatar', $user) }}" alt="" class="h-8 w-8 rounded-full object-cover bg-gray-100"
                                            onerror="this.style.display='none'">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->roles->pluck('name')->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->status->value === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $user->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>

                                        @if ($user->status->value === 'ACTIVE')
                                            <form method="POST" action="{{ route('users.suspend', $user) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-amber-600 hover:text-amber-900">{{ __('Suspend') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('users.activate', $user) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-900">{{ __('Activate') }}</button>
                                            </form>
                                        @endif
                                    @endcan

                                    @can('delete', $user)
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                            onsubmit="return confirm('{{ __('Delete this user?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
