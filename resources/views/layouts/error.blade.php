<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Terjadi kesalahan') - {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ url('assets/logo/antre-in-icon-light.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>html body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important; } .fas, .far, .fab { font-family: inherit !important; }</style>
    @stack('styles')
</head>
<body>
    <div id="app">
        <section class="section">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="card card-primary">
                            <div class="card-body text-center">
                                <div class="empty-state" data-height="400">
                                    <div class="empty-state-icon bg-danger"><i data-feather="alert-triangle" aria-hidden="true"></i></div>
                                    <h2>@yield('code')</h2>
                                    <p class="lead">@yield('message')</p>
                                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">Kembali</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <script src="{{ asset('js/app.min.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
</body>
</html>
