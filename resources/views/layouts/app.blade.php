<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DHL Rapporten') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <div class="min-h-screen">
        @auth
            <nav class="border-b border-gray-200 bg-white">
                <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                    <a href="{{ route('dashboard') }}" class="text-lg font-semibold">
                        DHL PDF Rapporten
                    </a>

                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-950">Overzicht</a>
                        <a href="{{ route('invoices.index') }}" class="text-gray-700 hover:text-gray-950">Facturen</a>
                        <a href="{{ route('reports.index') }}" class="text-gray-700 hover:text-gray-950">Rapporten</a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.users.index') }}" class="text-gray-700 hover:text-gray-950">Gebruikers</a>
                        @endif

                        <span class="text-gray-500">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-gray-900 px-3 py-2 text-white hover:bg-gray-700">
                                Uitloggen
                            </button>
                        </form>
                    </div>
                </div>
            </nav>
        @endauth

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if(session('status'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
