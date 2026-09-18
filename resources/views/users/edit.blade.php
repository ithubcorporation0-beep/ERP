<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }} — {{ $targetUser->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Email') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $targetUser->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Status') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $targetUser->status->label() }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('users.update-roles', $targetUser) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    @include('users._role_fields', [
                        'selectedRoles' => $targetUser->roles->pluck('name')->all(),
                        'selectedCustomerId' => $targetUser->customer_id,
                        'lockSuperAdmin' => $targetUser->hasRole(\App\Support\Roles::SUPER_ADMIN),
                    ])

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
