@extends('layouts.auth')

@section('title', 'Masuk')

@push('styles')
<style>
    .antre-login { min-height: 100vh; background: #f5f7ff; color: #26315c; }
    .antre-login__grid { display: grid; min-height: 100vh; grid-template-columns: minmax(0, 1.08fr) minmax(380px, .92fr); }
    .antre-login__intro { display: flex; flex-direction: column; justify-content: space-between; padding: clamp(32px, 6vw, 88px); background: #303fa9; color: #fff; }
    .antre-login__brand { display: inline-flex; align-items: center; gap: 11px; width: fit-content; color: #fff; font-size: 18px; font-weight: 800; text-decoration: none; }
    .antre-login__brand:hover, .antre-login__brand:focus { color: #fff; text-decoration: none; }
    .antre-login__brand img { width: 42px; height: 42px; border-radius: 9px; object-fit: contain; background: #fff; }
    .antre-login__copy { max-width: 540px; margin: auto 0; }
    .antre-login__kicker { margin: 0 0 16px; color: #ffd477; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .antre-login__copy h1 { max-width: 520px; margin: 0; color: #fff; font-size: clamp(32px, 4vw, 52px); font-weight: 800; line-height: 1.15; }
    .antre-login__copy p { max-width: 455px; margin: 20px 0 0; color: #e1e7ff; font-size: 16px; line-height: 1.7; }
    .antre-login__footer { color: #c7d0ff; font-size: 12px; }
    .antre-login__panel { display: flex; align-items: center; justify-content: center; padding: 32px; background: #fff; }
    .antre-login__card { width: 100%; max-width: 400px; }
    .antre-login__logo-mobile { display: none; width: 70px; height: 70px; margin: 0 auto 24px; object-fit: contain; }
    .antre-login__card h2 { margin: 0; color: #26315c; font-size: 28px; font-weight: 800; }
    .antre-login__subtitle { margin: 8px 0 30px; color: #667085; font-size: 14px; }
    .antre-login .form-group { margin-bottom: 20px; }
    .antre-login label { display: block; margin-bottom: 7px; color: #344054; font-size: 13px; font-weight: 700; }
    .antre-login__input-wrap { position: relative; }
    .antre-login__input-wrap > svg { position: absolute; top: 50%; left: 14px; z-index: 2; width: 18px; height: 18px; color: #98a2b3; transform: translateY(-50%); pointer-events: none; }
    .antre-login .form-control { height: 46px; border: 1px solid #d9def2; border-radius: 7px; padding-left: 44px; color: #26315c; font-size: 14px; }
    .antre-login__input-wrap > .form-control { padding-left: 50px !important; }
    .antre-login__input-wrap > .form-control[type="password"], .antre-login__input-wrap > .form-control[type="text"] { padding-right: 52px !important; }
    .antre-login .form-control:focus { border-color: #4c5bd4; box-shadow: 0 0 0 3px rgba(76,91,212,.15); }
    .antre-login .form-control.is-invalid { border-color: #e55353; }
    .antre-login__input-wrap .password-toggle { position: absolute; top: 50%; right: 9px; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; border: 0; border-radius: 6px; background: transparent; color: #667085; transform: translateY(-50%); cursor: pointer; }
    .antre-login__input-wrap .password-toggle:hover, .antre-login__input-wrap .password-toggle:focus { background: #eef0ff; color: #303fa9; outline: none; }
    .antre-login__input-wrap .password-toggle svg { width: 17px; height: 17px; }
    .antre-login__remember { display: flex; align-items: center; min-height: 24px; margin: 4px 0 24px; }
    .antre-login__remember input { width: 17px; height: 17px; margin: 0 8px 0 0; accent-color: #4c5bd4; }
    .antre-login__remember label { margin: 0; color: #667085; font-size: 13px; font-weight: 400; cursor: pointer; }
    .antre-login__submit { display: inline-flex; align-items: center; justify-content: center; width: 100%; min-height: 46px; border: 1px solid #4c5bd4; border-radius: 7px; background: #4c5bd4; color: #fff; font-size: 14px; font-weight: 800; transition: background .16s ease, border-color .16s ease; }
    .antre-login__submit:hover, .antre-login__submit:focus { border-color: #303fa9; background: #303fa9; color: #fff; }
    .antre-login__submit:disabled { cursor: not-allowed; opacity: .7; }
    .antre-login__spinner { display: none; width: 17px; height: 17px; margin-left: 9px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: antre-login-spin .8s linear infinite; }
    .antre-login__submit.is-loading .antre-login__spinner { display: inline-block; }
    .antre-login__submit.is-loading .antre-login__submit-text { opacity: .8; }
    .antre-login__alert { margin-bottom: 22px; }
    @keyframes antre-login-spin { to { transform: rotate(360deg); } }
    @media (max-width: 767.98px) {
        .antre-login__grid { grid-template-columns: 1fr; }
        .antre-login__intro { display: none; }
        .antre-login__panel { min-height: 100vh; padding: 28px 20px; }
        .antre-login__logo-mobile { display: block; }
    }
    @media (prefers-reduced-motion: reduce) { .antre-login * { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
</style>
@endpush

@section('content')
<div class="antre-login">
    <div class="antre-login__grid">
        <section class="antre-login__intro" aria-label="Tentang Antre-In">
            <a class="antre-login__brand" href="{{ url('/') }}">
                <img src="{{ url('assets/logo/antre-in-icon-light.png') }}" alt="Logo Antre-In" width="42" height="42">
                <span>Antre-In</span>
            </a>
            <div class="antre-login__copy">
                <p class="antre-login__kicker">Sistem kasir yang lebih teratur</p>
                <h1>Kelola transaksi tanpa antrean yang rumit.</h1>
                <p>Kelola produk, stok, transaksi, dan draft penjualan dari satu ruang kerja yang sederhana.</p>
            </div>
            <span class="antre-login__footer">&copy; {{ date('Y') }} Antre-In</span>
        </section>

        <main class="antre-login__panel">
            <div class="antre-login__card">
                <img class="antre-login__logo-mobile" src="{{ url('assets/logo/antre-in-icon-light.png') }}" alt="Logo Antre-In" width="70" height="70">
                <h2>Selamat datang</h2>
                <p class="antre-login__subtitle">Masuk untuk melanjutkan ke ruang kasir Anda.</p>

                @if ($errors->any())
                    <div class="alert alert-danger antre-login__alert" role="alert" tabindex="-1">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                    @csrf
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="antre-login__input-wrap">
                            <i data-feather="mail" aria-hidden="true"></i>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" autocomplete="email" aria-describedby="email-error" autofocus required>
                        </div>
                        @error('email')<div id="email-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password">Kata sandi</label>
                        <div class="antre-login__input-wrap">
                            <i data-feather="lock" aria-hidden="true"></i>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="current-password" aria-describedby="password-error" required>
                            <button type="button" class="password-toggle" id="password-toggle" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi"><i data-feather="eye" aria-hidden="true"></i></button>
                        </div>
                        @error('password')<div id="password-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="antre-login__remember">
                        <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">Ingat saya</label>
                    </div>
                    <button type="submit" class="antre-login__submit" id="login-submit">
                        <span class="antre-login__submit-text">Masuk</span><span class="antre-login__spinner" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var password = document.getElementById('password');
        var toggle = document.getElementById('password-toggle');
        var form = document.querySelector('.antre-login form');
        var submit = document.getElementById('login-submit');
        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                toggle.setAttribute('aria-label', visible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
                toggle.setAttribute('title', visible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
                toggle.textContent = '';
                var icon = document.createElement('i');
                icon.setAttribute('data-feather', visible ? 'eye' : 'eye-off');
                icon.setAttribute('aria-hidden', 'true');
                toggle.appendChild(icon);
                if (window.feather) { window.feather.replace(); }
            });
        }
        if (form && submit) {
            form.addEventListener('submit', function () {
                submit.disabled = true;
                submit.classList.add('is-loading');
            });
        }
    }());
</script>
@endpush
