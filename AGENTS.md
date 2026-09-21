# AGENTS.md — Pedoman Pengembangan antre-in

Pedoman ini wajib diikuti. Sumber kebenaran UI adalah [design.md](design.md).

## Stack

- Backend: Laravel 12 dan PHP sesuai composer.json.
- View/frontend: Blade dengan asset Otika yang dimuat langsung; jangan menggunakan Vite atau bundler frontend.
- UI: Otika Admin Template berbasis Bootstrap 4, jQuery, CSS Otika, Feather Icons, Font Awesome, dan plugin halaman.
- Listing: DataTables Otika bila diperlukan.
- Jangan mengasumsikan Metronic atau Bootstrap 5. Periksa implementasi aktual sebelum mengubah layout global.

## Laravel 12

- Route berada di routes/web.php dan routes/api.php bila digunakan.
- Controller di app/Http/Controllers, model di app/Models, view di resources/views.
- Middleware Laravel 12 dikonfigurasi di bootstrap/app.php; jangan membuat/mengubah app/Http/Kernel.php kecuali repository memang memakainya.
- Gunakan route model binding, migration, seeder, factory, policy, dan command Artisan standar.
- Jangan menggunakan `Route::resource` sebagai default. Jika resource route diperlukan, wajib mengecualikan `show`, `create`, dan `edit` (`->except(['show', 'create', 'edit'])`); form create/edit master selalu memakai modal di halaman index dan tidak memiliki halaman baru.
- Seluruh tabel database wajib menggunakan UUID v7 sebagai primary key. Foreign key ke tabel lain juga wajib bertipe UUID yang sesuai; jangan memakai auto-increment integer/bigint atau mencampur format identifier.
- Validasi default memakai $request->validate([...]); Form Request hanya untuk aturan kompleks atau reusable.
- Form normal mengembalikan redirect + flash message. AJAX mengembalikan JSON minimal dengan message dan errors: { field: [message] } bila ada error field.
- Gunakan transaction untuk operasi multi-tabel dan eager loading untuk mencegah N+1.
- Model baru memakai $fillable eksplisit. Periksa migration sebelum memilih integer atau UUID.
- Jangan mencatat password, token, secret, atau data sensitif.

## Struktur UI Otika

Struktur application page:

    #app
    └── .main-wrapper.main-wrapper-1
        ├── .navbar-bg
        ├── nav.main-navbar
        ├── .main-sidebar > #sidebar-wrapper
        ├── .main-content
        │   └── section.section > .section-body
        ├── .settingSidebar (opsional)
        └── footer.main-footer

- Gunakan blank.html atau halaman Otika terdekat sebagai referensi.
- Auth page tidak memakai sidebar; utility page mengikuti error/empty state Otika.
- Gunakan section-header, section-header-breadcrumb, section-body, row, col-12 col-md-* col-lg-*, card, card-header, card-body, card-footer, dan card-header-action.
- KPI memakai card-statistic-*.
- Bootstrap 4 memakai mr-*, ml-*, data-toggle, data-target, dan data-dismiss.
- Jangan memakai me-*, ms-*, data-bs-*, btn-close, data-kt-*, atau wrapper Metronic.
- Pertahankan data-toggle="sidebar", collapse-btn, fullscreen-btn, main-sidebar, #sidebar-wrapper, dan menu-toggle.nav-link.has-dropdown.
- Menu aktif memakai class active; icon memakai Feather atau Font Awesome sesuai bundle.
- Jangan mengedit vendor, app.min.js, atau scripts.js; gunakan custom.js, script halaman, atau override CSS.

## Asset dan dependency

Gunakan satu base URL asset yang dapat diganti saat deployment. Path Otika:

- Asset Otika (CSS, JS, DataTables, ikon, logo, favicon) wajib dipanggil dengan `asset()` dan mengikuti `ASSET_URL`; konfigurasi default mengarah ke `https://otika.namikulo.com/assets`, bukan ke host aplikasi lokal.
- File upload/media milik aplikasi yang disimpan melalui `storage:link` wajib dipanggil dengan `url('storage/...')` atau helper storage yang menghasilkan URL `/storage/...`; jangan memakai `asset()` atau `ASSET_URL` untuk file aplikasi.

- CSS: css/app.min.css, css/style.css, css/components.css, css/custom.css.
- JS: js/app.min.js, js/scripts.js, js/custom.js; page JS di js/page/.
- DataTables: bundles/datatables/datatables.min.css dan datatables.min.js.
- Media: img/logo.png, img/user.png, img/users/*, img/favicon.ico.

Urutan JS: vendor/global, plugin halaman, konfigurasi page, scripts.js, custom.js, lalu script halaman. Jangan memuat atau menginisialisasi plugin dua kali.

## Blade, form, dan CRUD

- Gunakan @extends terhadap layout aktual proyek dan @push('styles')/@push('scripts') bila stack tersedia.
- Setiap control memiliki label, id, name, pesan error, dan mempertahankan old(). Setiap gambar memiliki alt.
- Sediakan loading, empty, success, error, dan permission state.
- CRUD master memakai modal create/edit dan modal delete bila itu pola modul terkait.
- Modal memakai Bootstrap 4: modal, modal-dialog, modal-content, modal-header, modal-body, modal-footer, data-toggle="modal", dan data-dismiss="modal".
- Selalu gunakan @csrf; edit memakai method spoofing PUT/PATCH.
- Tombol delete menyimpan data-id dan data-name; form delete memakai @method('DELETE') dan action resource/{id}.
- Jangan membuat create.blade.php/edit.blade.php untuk master data yang menggunakan modal.
- Plugin Otika yang tersedia antara lain select2, selectric, datepicker, datetimepicker, daterange, timepicker, summernote, dropzone, dan image-preview. Uji plugin dalam modal.

## JavaScript dan AJAX

- Default CRUD boleh submit normal + redirect.
- AJAX boleh memakai fetch() atau jQuery AJAX sesuai pola modul; sertakan CSRF token dari Blade.
- PUT, PATCH, dan DELETE via FormData memakai _method.
- Tangani status 422 dengan errors, is-invalid, dan invalid-feedback per field.
- Sediakan loading state, escape konten user sebelum innerHTML, dan pastikan script aman bila element target tidak ada.
- Flash menggunakan mekanisme layout/Otika yang tersedia. Jangan menambah SweetAlert, Toastr, atau library baru bila belum menjadi dependency/pola existing.

## Tabel dan DataTables

- Gunakan table-responsive dan Bootstrap 4 table table-striped.
- Client-side default: pageLength 10, ordering sesuai kebutuhan, responsive true.
- Server-side hanya untuk dataset besar dan endpoint/query harus terdokumentasi.
- Export mengikuti Buttons Otika yang tersedia: copy, csv, excel, pdf, print.
- Jangan menginisialisasi DataTables dua kali pada element yang sama.

## Routing, authorization, dan keamanan

- Gunakan nama route Laravel seperti resources.index, resources.store, dan resources.update.
- Jika memakai resource route untuk master, gunakan `Route::resource(...)->except(['show', 'create', 'edit'])`; seluruh create/edit dilakukan melalui modal pada halaman index.
- Proteksi route dengan auth dan middleware role/policy.
- Alias middleware baru di Laravel 12 didaftarkan melalui withMiddleware(...) di bootstrap/app.php.
- Gunakan Policy, can middleware, atau $this->authorize(...); @can di Blade hanya mengatur visibilitas.
- Validasi ownership/scope di server dan cegah IDOR, CSRF, XSS, upload berbahaya, mass assignment, serta kebocoran credential.
- File public storage memakai url('storage/...'), bukan asset(), agar tidak terpengaruh ASSET_URL.

## Logging

Jika tersedia system_logs, catat created, updated, deleted, assigned, unassigned, login, dan logout dengan user, tabel, record, method, URL, IP, serta snapshot old/new yang relevan. Bersihkan password, token, secret, dan file binary dari payload. Baca migration aktual sebelum mengasumsikan schema.

## Quality gate

Jalankan command relevan sebelum menyerahkan perubahan:

    php artisan test
    php artisan route:list
    git diff --check

Periksa render view, auth, authorization, CSRF, validasi, flash/AJAX response, asset 404, browser console, serta tampilan pada 375px, 768px, 1024px, dan desktop.

## Anti-pattern

- Jangan memakai Metronic, Bootstrap 5, data-bs-*, btn-close, me-*, ms-*, atau data-kt-* pada halaman Otika.
- Jangan mengedit vendor, app.min.js, atau scripts.js.
- Jangan memuat plugin dua kali.
- Jangan menaruh credential, API key, atau data dummy production di repository/view.
- Jangan menambah Form Request, library notifikasi, chart, atau plugin tanpa kebutuhan nyata.
- Jangan mengubah layout global untuk masalah satu halaman; gunakan override page-specific.

## Checklist halaman baru

- [ ] Layout, wrapper, navbar, sidebar, breadcrumb, active menu, dan footer mengikuti Otika.
- [ ] Asset memakai base URL deployment/config konsisten.
- [ ] Bootstrap 4 attributes/classes digunakan.
- [ ] Form memiliki label, CSRF, validasi, error state, dan old input.
- [ ] CRUD master memakai modal bila sesuai pola.
- [ ] Listing memakai markup Otika dan DataTables bila diperlukan.
- [ ] Authorization route/controller dan visibilitas action konsisten.
- [ ] Flash/AJAX response jelas.
- [ ] Responsive, accessibility, loading, empty, success, dan error state diuji.
- [ ] Test/lint dan git diff --check dijalankan.
