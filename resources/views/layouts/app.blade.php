<!doctype html>
<html data-bs-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'SWUDB'))</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- inclusion -->
    @yield('include')

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @livewireStyles
</head>
<style>
    .innerCarta{
        height: 100%;
    }
    
    .comune{
        color: #8B4513;
    }

    .noncomune{
        color: white;
    }

    .rara{
        color: yellow;
    }

    .leggendaria{
        color: lightblue;
    }

    .speciale{
        color: #a6a594;
    }

    .bg-custom-light{
        background-color: #555555;
    }
    nav{
        z-index: 1021;
    }
    a{
        text-decoration: none;
        color: inherit;
    }
</style>
<body>
    <div id="app" class="d-flex flex-column justify-content-between min-vh-100">

        <main class="py-4 flex-grow-1">
            <div class="container content @yield('content-class')">
                @yield('content')
            </div>
        </main>

    </div>

    @vite('resources/js/alpinejs-config.js')
    @livewireScripts
    @stack('scripts')
    @yield('script')
</body>
@yield('style')
</html>
@yield("php")

