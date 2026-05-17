<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DHL Rapporten') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <div class="min-h-screen">
        @auth
            <nav class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 md:flex md:items-center md:justify-between">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('dashboard') }}" class="text-base font-semibold sm:text-lg">
                            DHL PDF Rapporten
                        </a>

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 md:hidden"
                            aria-controls="main-navigation"
                            aria-expanded="false"
                            data-menu-button
                        >
                            <span class="sr-only">Menu</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm.75 3.5a.75.75 0 0 0 0 1.5h12.5a.75.75 0 0 0 0-1.5H3.75Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div
                        id="main-navigation"
                        class="mt-4 hidden flex-col gap-3 border-t border-gray-100 pt-4 text-sm md:mt-0 md:flex md:flex-row md:items-center md:justify-end md:gap-4 md:border-t-0 md:pt-0"
                        data-menu
                    >
                        <a href="{{ route('dashboard') }}" class="block rounded-md px-2 py-2 text-gray-700 hover:bg-gray-50 hover:text-gray-950 md:px-0 md:py-0 md:hover:bg-transparent">Overzicht</a>
                        <a href="{{ route('invoices.index') }}" class="block rounded-md px-2 py-2 text-gray-700 hover:bg-gray-50 hover:text-gray-950 md:px-0 md:py-0 md:hover:bg-transparent">Facturen</a>
                        <a href="{{ route('failed-invoices.index') }}" class="block rounded-md px-2 py-2 text-gray-700 hover:bg-gray-50 hover:text-gray-950 md:px-0 md:py-0 md:hover:bg-transparent">Mislukte uploads</a>
                        <a href="{{ route('reports.index') }}" class="block rounded-md px-2 py-2 text-gray-700 hover:bg-gray-50 hover:text-gray-950 md:px-0 md:py-0 md:hover:bg-transparent">Rapporten</a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.users.index') }}" class="block rounded-md px-2 py-2 text-gray-700 hover:bg-gray-50 hover:text-gray-950 md:px-0 md:py-0 md:hover:bg-transparent">Gebruikers</a>
                        @endif

                        <span class="break-words px-2 py-2 text-gray-500 md:max-w-40 md:truncate md:px-0 md:py-0">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('logout') }}" class="w-full md:w-auto">
                            @csrf
                            <button type="submit" class="w-full rounded-md bg-gray-900 px-3 py-2 text-left text-white hover:bg-gray-700 md:w-auto md:text-center">
                                Uitloggen
                            </button>
                        </form>
                    </div>
                </div>
            </nav>
        @endauth

        <main class="mx-auto max-w-7xl px-4 py-6 text-sm sm:px-6 sm:py-8 sm:text-base lg:px-8">
            @if(session('status'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-menu-button]').forEach((button) => {
            const menu = document.getElementById(button.getAttribute('aria-controls'));

            if (!menu) {
                return;
            }

            button.addEventListener('click', () => {
                const isOpen = !menu.classList.toggle('hidden');
                menu.classList.toggle('flex', isOpen);
                button.setAttribute('aria-expanded', String(isOpen));
            });
        });
    </script>
</body>
</html>
