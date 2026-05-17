@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-medium uppercase tracking-wide text-yellow-700">Admin</p>
        <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">Gebruiker toevoegen</h1>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="w-full max-w-2xl rounded-lg bg-white p-4 shadow-sm sm:p-6">
        @csrf

        <div class="space-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Naam</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500 sm:text-base">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500 sm:text-base">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Rol</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500 sm:text-base">
                    <option value="user" @selected(old('role', 'user') === 'user')>Gebruiker</option>
                    <option value="admin" @selected(old('role') === 'admin')>Beheerder</option>
                </select>
                @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Wachtwoord</label>
                    <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500 sm:text-base">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Herhaal wachtwoord</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500 sm:text-base">
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <button type="submit" class="w-full rounded-md bg-yellow-500 px-4 py-2 font-semibold text-gray-950 hover:bg-yellow-400 sm:w-auto">
                Opslaan
            </button>
            <a href="{{ route('admin.users.index') }}" class="w-full rounded-md px-4 py-2 text-center text-sm font-medium text-gray-600 hover:text-gray-950 sm:w-auto">Annuleren</a>
        </div>
    </form>
@endsection
