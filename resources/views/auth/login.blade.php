@extends('layouts.auth')

@section('title', 'Masuk')

@push('styles')
<style>
    :root { --antre-ink: #20264f; --antre-indigo: #4d5bd6; --antre-indigo-dark: #2e3a9f; --antre-amber: #ffb52e; --antre-paper: #f6f5f0; --antre-muted: #727891; --antre-line: #dedfe8; }
    html, body { min-width: 320px; background: var(--antre-paper); }
    .antre-login { min-height: 100vh; min-height: 100dvh; overflow: hidden; background: var(--antre-paper); color: var(--antre-ink); }
    .antre-login__layout { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(460px, .92fr); min-height: 100vh; min-height: 100dvh; }
    .antre-login__visual { position: relative; display: flex; flex-direction: column; justify-content: space-between; min-height: 680px; overflow: hidden; padding: clamp(32px, 5vw, 72px); background: var(--antre-indigo-dark); color: #fff; isolation: isolate; }
    .antre-login__visual::before { position: absolute; top: -17%; right: -12%; width: 58%; height: 72%; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; content: ''; z-index: -1; }
    .antre-login__visual::after { position: absolute; right: 12%; bottom: -18%; width: 48%; height: 48%; border: 1px solid rgba(255,181,46,.32); border-radius: 50%; content: ''; z-index: -1; }
    .antre-login__brand { display: inline-flex; align-items: center; gap: 12px; width: fit-content; color: #fff; font-size: 18px; font-weight: 800; letter-spacing: -.02em; text-decoration: none; }
    .antre-login__brand:hover, .antre-login__brand:focus { color: #fff; text-decoration: none; }
    .antre-login__brand img { width: 42px; height: 42px; border-radius: 12px; object-fit: contain; box-shadow: 0 8px 24px rgba(15,20,75,.24); }
    .antre-login__visual-content { position: relative; max-width: 630px; margin: auto 0; padding: 56px 0 48px; }
    .antre-login__eyebrow { display: inline-flex; align-items: center; gap: 8px; margin: 0 0 20px; color: #ffd477; font-size: 11px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .antre-login__eyebrow::before { width: 28px; height: 2px; background: var(--antre-amber); content: ''; }
    .antre-login__visual h1 { max-width: 610px; margin: 0; color: #fff; font-size: clamp(38px, 4.7vw, 70px); font-weight: 800; letter-spacing: -.055em; line-height: 1.03; }
    .antre-login__visual-copy { max-width: 450px; margin: 24px 0 0; color: #d9ddff; font-size: 16px; line-height: 1.7; }
    .antre-login__ticket { position: absolute; right: 3%; bottom: 7%; width: 210px; padding: 18px; border: 1px solid rgba(255,255,255,.2); border-radius: 16px; background: rgba(255,255,255,.1); box-shadow: 0 22px 44px rgba(19,27,94,.24); transform: rotate(-7deg); }
    .antre-login__ticket-label { margin-bottom: 18px; color: #c8ceff; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
    .antre-login__ticket-number { display: flex; align-items: baseline; gap: 8px; color: #fff; font-size: 42px; font-weight: 800; letter-spacing: -.06em; line-height: 1; }
    .antre-login__ticket-number span { color: #ffd477; font-size: 13px; font-weight: 700; letter-spacing: 0; }
    .antre-login__ticket-status { display: flex; align-items: center; gap: 7px; margin-top: 18px; color: #f6f8ff; font-size: 11px; }
    .antre-login__ticket-status::before { width: 7px; height: 7px; border-radius: 50%; background: #6be0a3; box-shadow: 0 0 0 4px rgba(107,224,163,.14); content: ''; }
    .antre-login__footer { display: flex; align-items: center; justify-content: space-between; gap: 20px; color: #aeb7ee; font-size: 12px; }
    .antre-login__footer-note { display: inline-flex; align-items: center; gap: 7px; }
    .antre-login__footer-note svg { width: 14px; height: 14px; }
    .antre-login__form-side { display: flex; flex-direction: column; justify-content: center; padding: clamp(28px, 6vw, 88px) clamp(24px, 7vw, 100px); background: var(--antre-paper); }
    .antre-login__form-top { display: flex; justify-content: flex-end; min-height: 42px; margin-bottom: clamp(38px, 7vh, 80px); }
    .antre-login__form-top span { display: inline-flex; align-items: center; gap: 7px; color: var(--antre-muted); font-size: 12px; }
    .antre-login__form-top svg { width: 15px; height: 15px; color: var(--antre-indigo); }
    .antre-login__card { width: 100%; max-width: 430px; margin: 0 auto; }
    .antre-login__mobile-brand { display: none; width: 48px; height: 48px; margin-bottom: 28px; border-radius: 13px; }
    .antre-login__card h2 { margin: 0; color: var(--antre-ink); font-size: clamp(30px, 3vw, 40px); font-weight: 800; letter-spacing: -.045em; line-height: 1.08; }
    .antre-login__subtitle { max-width: 340px; margin: 13px 0 38px; color: var(--antre-muted); font-size: 14px; line-height: 1.65; }
    .antre-login .form-group { margin-bottom: 22px; }
    .antre-login label { display: block; margin-bottom: 8px; color: var(--antre-ink); font-size: 12px; font-weight: 800; letter-spacing: .01em; }
    .antre-login__input-wrap { position: relative; }
    .antre-login__input-wrap > svg { position: absolute; top: 50%; left: 16px; z-index: 2; width: 17px; height: 17px; color: #8b91aa; transform: translateY(-50%); pointer-events: none; }
    .antre-login .form-control { height: 52px; border: 1px solid var(--antre-line); border-radius: 12px; padding: 0 16px 0 50px !important; background: rgba(255,255,255,.56); color: var(--antre-ink); font-size: 14px; box-shadow: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .antre-login__input-wrap > .form-control[type="password"], .antre-login__input-wrap > .form-control[type="text"] { padding-right: 54px !important; }
    .antre-login .form-control:focus { border-color: var(--antre-indigo); background: #fff; box-shadow: 0 0 0 4px rgba(77,91,214,.13); }
    .antre-login .form-control.is-invalid { border-color: #d94c5c; }
    .antre-login .invalid-feedback { margin-top: 7px; color: #bd3547; font-size: 12px; }
    .antre-login__input-wrap .password-toggle { position: absolute; top: 50%; right: 8px; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; padding: 0; border: 0; border-radius: 9px; background: transparent; color: #737b99; transform: translateY(-50%); cursor: pointer; }
    .antre-login__input-wrap .password-toggle:hover, .antre-login__input-wrap .password-toggle:focus { outline: none; background: #eceeff; color: var(--antre-indigo-dark); }
    .antre-login__input-wrap .password-toggle svg { width: 17px; height: 17px; }
    .antre-login__options { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: 2px 0 28px; }
    .antre-login__remember { display: inline-flex; align-items: center; min-height: 28px; }
    .antre-login__remember input { width: 17px; height: 17px; margin: 0 8px 0 0; accent-color: var(--antre-indigo); }
    .antre-login__remember label { margin: 0; color: var(--antre-muted); font-size: 12px; font-weight: 600; cursor: pointer; }
    .antre-login__secure-note { display: inline-flex; align-items: center; gap: 5px; color: var(--antre-muted); font-size: 11px; }
    .antre-login__secure-note svg { width: 14px; height: 14px; color: #47a878; }
    .antre-login__submit { display: inline-flex; align-items: center; justify-content: center; width: 100%; min-height: 52px; border: 1px solid var(--antre-indigo); border-radius: 12px; background: var(--antre-indigo); color: #fff; font-size: 14px; font-weight: 800; box-shadow: 0 10px 22px rgba(77,91,214,.2); transition: background .18s ease, border-color .18s ease, transform .18s ease, box-shadow .18s ease; }
    .antre-login__submit:hover, .antre-login__submit:focus { border-color: var(--antre-indigo-dark); background: var(--antre-indigo-dark); color: #fff; box-shadow: 0 13px 26px rgba(46,58,159,.26); transform: translateY(-1px); }
    .antre-login__submit:focus-visible, .antre-login__input-wrap .password-toggle:focus-visible { outline: 3px solid rgba(255,181,46,.72); outline-offset: 3px; }
    .antre-login__submit:disabled { cursor: not-allowed; opacity: .72; transform: none; }
    .antre-login__spinner { display: none; width: 17px; height: 17px; margin-left: 9px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: antre-login-spin .8s linear infinite; }
    .antre-login__submit.is-loading .antre-login__spinner { display: inline-block; }
    .antre-login__submit.is-loading .antre-login__submit-text { opacity: .8; }
    .antre-login__alert { margin-bottom: 24px; }
    @keyframes antre-login-spin { to { transform: rotate(360deg); } }
    @media (max-width: 1100px) { .antre-login__layout { grid-template-columns: minmax(0,1fr) minmax(410px,.9fr); } .antre-login__ticket { right: 4%; bottom: 6%; transform: scale(.9) rotate(-7deg); transform-origin: bottom right; } }
    @media (max-width: 767.98px) {
        .antre-login__layout { display: block; }
        .antre-login__visual { display: block; min-height: auto; padding: 22px 20px; }
        .antre-login__visual::before, .antre-login__visual::after, .antre-login__visual-content, .antre-login__footer { display: none; }
        .antre-login__brand img { width: 36px; height: 36px; border-radius: 10px; }
        .antre-login__brand { font-size: 16px; }
        .antre-login__form-side { min-height: calc(100vh - 80px); min-height: calc(100dvh - 80px); padding: 38px 20px 30px; }
        .antre-login__form-top { display: none; }
        .antre-login__mobile-brand { display: block; }
        .antre-login__subtitle { margin-bottom: 32px; }
        .antre-login__options { align-items: flex-start; flex-direction: column; gap: 10px; }
    }
    @media (prefers-reduced-motion: reduce) { .antre-login *, .antre-login *::before, .antre-login *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
</style>
@endpush

@section('content')
<div class="antre-login">
    <div class="antre-login__layout">
        <section class="antre-login__visual" aria-label="Tentang Antre-In">
            <a class="antre-login__brand" href="{{ url('/') }}">
                <img src="{{ url('assets/logo/antre-in-icon-light.png') }}" alt="Logo Antre-In" width="42" height="42">
                <span>Antre-In</span>
            </a>
            <div class="antre-login__visual-content">
                <p class="antre-login__eyebrow">Ruang kerja kasir yang rapi</p>
                <h1>Semua transaksi, satu alur yang lebih tenang.</h1>
                <p class="antre-login__visual-copy">Pantau penjualan, stok, dan antrean pelanggan dari satu tempat yang terasa ringan digunakan setiap hari.</p>
                <div class="antre-login__ticket" aria-label="Status antrean saat ini">
                    <div class="antre-login__ticket-label">Status operasional</div>
                    <div class="antre-login__ticket-number">A-24 <span>sedang dilayani</span></div>
                    <div class="antre-login__ticket-status">Sistem berjalan normal</div>
                </div>
            </div>
            <div class="antre-login__footer">
                <span class="antre-login__footer-note"><i data-feather="shield" aria-hidden="true"></i> Data tersimpan dengan aman</span>
                <span>&copy; {{ date('Y') }} Antre-In</span>
            </div>
        </section>

        <main class="antre-login__form-side">
            <div class="antre-login__form-top"><span><i data-feather="lock" aria-hidden="true"></i> Akses staf terverifikasi</span></div>
            <div class="antre-login__card">
                <img class="antre-login__mobile-brand" src="{{ url('assets/logo/antre-in-icon-light.png') }}" alt="Logo Antre-In" width="48" height="48">
                <h2>Selamat datang kembali</h2>
                <p class="antre-login__subtitle">Masuk untuk melanjutkan ke ruang kerja kasir Anda.</p>
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
                    <div class="antre-login__options">
                        <div class="antre-login__remember">
                            <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember">Ingat saya</label>
                        </div>
                        <span class="antre-login__secure-note"><i data-feather="check-circle" aria-hidden="true"></i> Login aman &amp; terenkripsi</span>
                    </div>
                    <button type="submit" class="antre-login__submit" id="login-submit"><span class="antre-login__submit-text">Masuk ke dashboard</span><span class="antre-login__spinner" aria-hidden="true"></span></button>
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
