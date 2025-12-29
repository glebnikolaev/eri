<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>{{ config('app.name', 'Поиск участков и домов') }}</title>
        @vite(['resources/js/app.js'])
        @stack('scripts')
    </head>
    <body class="d-flex flex-column h-100">
        @include('header')
        <main class="flex-shrink-0">
            @yield('content')
        </main>
        @include('footer')
    </body>
</html>
