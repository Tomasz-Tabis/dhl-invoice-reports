@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-md rounded-lg bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold">Inloggen</h1>
        <p class="mt-2 text-sm text-gray-600">Alleen toegang voor aangemaakte accounts.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Wachtwoord</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                Onthoud mij
            </label>

            <button type="submit" class="w-full rounded-md bg-yellow-500 px-4 py-2 font-semibold text-gray-950 hover:bg-yellow-400">
                Inloggen
            </button>
        </form>
    </div>
@endsection
