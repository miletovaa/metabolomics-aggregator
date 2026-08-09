<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>ORG Database Manager & Aggregator</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Not @livewireScripts: resources/js/app.js already imports Livewire/Alpine
             from vendor and calls Livewire.start()/Alpine.start() itself (so the bundle
             goes through Vite like the rest of the app's JS). @livewireScripts would load
             AND auto-start a second, separate Livewire runtime on the same page — two
             instances fighting over the same wire:id DOM nodes, which is what caused
             "Public method [$commit]/[$set] not found" errors when submitting forms.
             @livewireScriptConfig only emits the runtime config (CSRF token, update
             endpoint) as inline vars for our own bundle's Livewire.start() to pick up. --}}
        @livewireScriptConfig
    </body>
</html>
