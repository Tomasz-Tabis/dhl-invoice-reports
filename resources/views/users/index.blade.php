@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col items-stretch gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Admin</p>
            <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">Gebruikers</h1>
        </div>

        <a href="{{ route('admin.users.create') }}" class="w-full rounded-md bg-yellow-500 px-4 py-2 text-center font-semibold text-gray-950 hover:bg-yellow-400 sm:w-auto">
            Gebruiker toevoegen
        </a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Naam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Aangemaakt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($users as $user)
                    <tr>
                        <td class="max-w-xs break-words px-6 py-4 text-sm font-medium">{{ $user->name }}</td>
                        <td class="max-w-xs break-words px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ $user->role }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $user->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Geen gebruikers.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
@endsection
