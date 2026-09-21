# Keputusan Pengembangan antre-in

- Frontend fase ini memakai Blade dan asset Otika langsung melalui `asset()` dengan `ASSET_URL`; workflow Vite dan bundler frontend dihapus dari konfigurasi pengembangan.
- `AdminSeeder` tidak membuat akun jika `ADMIN_EMAIL` atau `ADMIN_PASSWORD` kosong, dan menampilkan peringatan agar tidak ada kredensial default di repository.
- Dependency Composer di-resolve ulang ke versi yang mendukung PHP 8.2 sesuai `config.platform.php`, tanpa menambah library aplikasi.
- Menu untuk modul fase berikutnya tetap disiapkan di sidebar dan menggunakan tautan aman `#` sampai route modul tersebut tersedia.
- Primary key dan foreign key tabel aplikasi Fase 1 menggunakan UUID v7 melalui trait `HasUuids`; ini mengikuti aturan terbaru `AGENTS.md` meskipun tipe lama pada PRD masih menyebut integer.
- Resource route master mengecualikan `show`, `create`, dan `edit` karena CRUD master wajib memakai modal dan tidak memiliki view create/edit terpisah.
- FASE 3 menyiapkan seluruh kolom transaksi draft sejak awal pada tabel `sales`, tetapi endpoint yang diaktifkan baru checkout langsung; alur draft akan memakai baris yang sama pada fase berikutnya.
- Nomor invoice/draft memakai tabel `number_sequences` bertipe dan bertanggal, dengan lebar minimum empat digit untuk invoice dan tiga digit untuk draft.
- Struk dibuat sebagai view mandiri 80 mm agar tidak membawa sidebar/navbar Otika dan mudah dicetak melalui `window.print()`.
- FASE 4 menyimpan snapshot item draft saat draft dibuat, lalu resume selalu membaca harga/stok terbaru; perubahan tersebut baru dipersistenkan saat draft diperbarui atau checkout.
- Kunci draft aktif berlaku berdasarkan `config('pos.draft_lock_minutes')`; kunci basi dapat diambil alih dan draft aktif yang sedang terkunci tidak dipruning sebelum kuncinya basi.
- FASE 5 menambahkan tabel `sale_status_histories` ber-UUID untuk menyimpan transisi draft, selesai, void, dan dibuang beserta pelaku/waktunya; data transaksi lama tetap memiliki fallback timeline dari kolom timestamp `sales`.
- FASE 7 menyimpan audit aktivitas pada `system_logs`; payload dibersihkan rekursif dari password/token/secret dan file atau binary, sedangkan tampilan audit membatasi rentang maksimal 30 hari.
