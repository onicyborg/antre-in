<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') - {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ url('assets/logo/antre-in-icon-light.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>html body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important; } .fas, .far, .fab { font-family: inherit !important; }</style>
    @stack('styles')
</head>
<body>
    <div class="loader"></div>
    <div id="app">
        @yield('content')
    </div>
    <script src="{{ asset('js/app.min.js') }}"></script>
    @stack('vendor-scripts')
    @stack('page-scripts-before-template')
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    @stack('scripts')
</body>
</html>
