@extends('layouts.app')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Admin</p>
            <h1 class="mt-1 text-3xl font-semibold">Gebruikers</h1>
        </div>

        <a href="{{ route('admin.users.create') }}" class="rounded-md bg-yellow-500 px-4 py-2 font-semibold text-gray-950 hover:bg-yellow-400">
            Gebruiker toevoegen
        </a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
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
                        <td class="px-6 py-4 text-sm font-medium">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ $user->role }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $user->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Geen gebruikers.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
@endsection
