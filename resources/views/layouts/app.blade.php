<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ url('assets/logo/antre-in-icon-light.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.10/dist/sweetalert2.min.css">
    <style>
        /* CDN Otika tidak mengirim CORS untuk font. Gunakan font lokal dan SVG Feather. */
        html body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important; }
        .fas, .far, .fab { font-family: inherit !important; }
        .header-logo { object-fit: contain; }
        .antre-profile { display: inline-flex !important; align-items: center; min-height: 48px; padding: 5px 12px 5px 8px !important; border: 1px solid transparent; border-radius: 8px; color: #34395e !important; }
        .antre-profile:hover, .antre-profile[aria-expanded="true"] { border-color: #e4e7f5; background: #f7f8ff; }
        .antre-profile img { flex: 0 0 auto; width: 36px; height: 36px; object-fit: contain; background: #eef0ff; }
        .antre-profile__copy { display: inline-flex; flex-direction: column; min-width: 92px; text-align: left; line-height: 1.25; }
        .antre-profile__name { overflow: hidden; color: #34395e; font-size: 13px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .antre-profile__copy small { color: #98a2b3; font-size: 10px; font-weight: 600; }
        @media (max-width: 575.98px) { .antre-profile { padding-right: 6px !important; } .antre-profile__copy { display: none; } }
    </style>
    @stack('styles')
</head>
<body class="light light-sidebar theme-white">
    <div class="loader"></div>
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            @include('partials.navbar')
            @include('partials.sidebar')

            <div class="main-content">
                @include('partials.flash')
                <section class="section">
                    @yield('content')
                </section>
            </div>

            @include('partials.footer')
        </div>
    </div>
    <script src="{{ asset('js/app.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.10/dist/sweetalert2.all.min.js"></script>
    @stack('vendor-scripts')
    @stack('page-scripts-before-template')
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    <style>
        .card-statistic-1 .card-icon { display: flex; align-items: center; justify-content: center; }
        .card-statistic-1 .card-icon i,
        .card-statistic-1 .card-icon svg { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; color: #fff; stroke: #fff; font-size: 22px; line-height: 1; }
        .card-statistic-1 .card-icon.bg-warning i,
        .card-statistic-1 .card-icon.bg-warning svg,
        .card-statistic-1 .card-icon.bg-light i,
        .card-statistic-1 .card-icon.bg-light svg,
        .card-statistic-1 .card-icon.bg-white i,
        .card-statistic-1 .card-icon.bg-white svg { color: #34395e; stroke: #34395e; }
        .sidebar-menu > li > a svg { width: 16px; height: 16px; stroke-width: 2.1; }
        .sidebar-menu > li.active > a svg { color: #6777ef; stroke: #6777ef; }
    </style>
    <script>
        (function () {
            var iconMap = {
                'fa-shopping-cart': 'shopping-cart', 'fa-receipt': 'file-text',
                'fa-pause': 'pause-circle', 'fa-boxes': 'archive',
                'fa-cash-register': 'credit-card', 'fa-exclamation': 'alert-triangle',
                'fa-check-circle': 'check-circle', 'fa-exclamation-circle': 'alert-circle',
                'fa-sign-out-alt': 'log-out', 'fa-edit': 'edit', 'fa-plus': 'plus',
                'fa-trash': 'trash-2', 'fa-chart-bar': 'bar-chart-2', 'fa-box': 'box',
                'fa-chart-line': 'trending-up', 'fa-money-bill-wave': 'dollar-sign',
                'fa-coins': 'dollar-sign', 'fa-exclamation-triangle': 'alert-triangle',
                'fa-clipboard-check': 'clipboard', 'fa-clipboard-list': 'clipboard',
                'fa-filter': 'filter', 'fa-trophy': 'award', 'fa-cubes': 'package'
            };
            document.querySelectorAll('i.fas, i.far, i.fab').forEach(function (icon) {
                var featherName = null;
                Object.keys(iconMap).some(function (className) {
                    if (icon.classList.contains(className)) {
                        featherName = iconMap[className];
                        return true;
                    }
                    return false;
                });
                if (featherName) {
                    icon.className = '';
                    icon.setAttribute('data-feather', featherName);
                    icon.setAttribute('aria-hidden', 'true');
                }
            });
            if (window.feather) { window.feather.replace(); }
        }());

        // Bootstrap modal harus berada langsung di body agar tidak tertutup stacking context Otika.
        $(function () {
            $('.modal').each(function () {
                if (!$(this).parent().is('body')) {
                    $(this).appendTo('body');
                }
            });
            $('.modal-dialog > .modal-footer').each(function () {
                var footer = $(this);
                var content = footer.siblings('.modal-content').first();
                if (!content.length) return;
                var form = content.find('form').first();
                footer.appendTo(content);
                if (form.length && form.attr('id')) {
                    footer.find('button[type="submit"]').attr('form', form.attr('id'));
                }
            });
        });
    </script>
    @stack('scripts')
    <script>
        (function () {
            if (!window.Swal) return;
            var flash = document.getElementById('app-flash');
            var alertOptions = { confirmButtonText: 'Mengerti', buttonsStyling: true };
            if (flash) {
                var success = flash.dataset.success;
                var error = flash.dataset.error || flash.dataset.validation;
                if (success) window.Swal.fire(Object.assign({}, alertOptions, { icon: 'success', title: 'Berhasil', text: success }));
                else if (error) window.Swal.fire(Object.assign({}, alertOptions, { icon: 'error', title: 'Gagal', text: error }));
            }

            document.addEventListener('click', function (event) {
                var draftButton = event.target.closest ? event.target.closest('#draft-list button.btn-outline-danger') : null;
                if (!draftButton || draftButton.dataset.sweetConfirmed === '1') return;
                event.preventDefault();
                event.stopPropagation();
                window.Swal.fire({
                    icon: 'warning', title: 'Buang draft?', text: 'Draft ini akan dibuang dan tidak dapat dipulihkan.',
                    showCancelButton: true, confirmButtonText: 'Ya, buang', cancelButtonText: 'Batal',
                    reverseButtons: true, buttonsStyling: true
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    draftButton.dataset.sweetConfirmed = '1';
                    var nativeConfirm = window.confirm;
                    window.confirm = function () { return true; };
                    draftButton.click();
                    window.confirm = nativeConfirm;
                    delete draftButton.dataset.sweetConfirmed;
                });
            }, true);

            document.addEventListener('click', function (event) {
                var button = event.target.closest ? event.target.closest('.btn-delete') : null;
                if (!button) return;
                event.preventDefault();
                event.stopPropagation();
                window.Swal.fire({
                    icon: 'warning', title: 'Hapus data?',
                    text: 'Data "' + (button.dataset.name || '') + '" akan dihapus. Tindakan ini tidak dapat dibatalkan.',
                    showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal',
                    reverseButtons: true, buttonsStyling: true
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    var form = document.getElementById('deleteForm');
                    if (!form) return;
                    form.action = button.dataset.url || '#';
                    form.submit();
                });
            }, true);

            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!form.matches) return;
                var deleteMethod = form.querySelector('input[name="_method"][value="DELETE"]');
                var confirmation = form.dataset.sweetConfirm || (deleteMethod ? 'Data ini akan dihapus atau dibuang dan tidak dapat dipulihkan.' : '');
                if (!confirmation) return;
                event.preventDefault();
                event.stopPropagation();
                window.Swal.fire({
                    icon: 'warning', title: 'Konfirmasi tindakan', text: confirmation,
                    showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal',
                    reverseButtons: true, buttonsStyling: true
                }).then(function (result) { if (result.isConfirmed) form.submit(); });
            }, true);
        }());
    </script>
</body>
</html>
