# PRD — antre-in (Website POS dengan Draft Transaksi)

Versi 1.0 · Bahasa antarmuka: Indonesia · Target eksekusi: Codex (atau agent coding lain)

Dokumen ini dibaca bersama dua file lain di root repository:

- `AGENTS.md`: aturan kode Laravel 12 dan struktur UI Otika (Bootstrap 4).
- `design.md`: panduan komponen dan asset Otika.

Urutan prioritas jika ada konflik: **AGENTS.md** dan **design.md** untuk konvensi kode dan UI, **PRD.md** untuk perilaku produk dan data. Jika ada hal yang tidak diatur, ambil keputusan paling sederhana yang konsisten dengan dokumen ini, lalu catat di `docs/DECISIONS.md`.

---

## 1. Ringkasan Produk

**antre-in** adalah aplikasi web Point of Sale (POS) sederhana untuk toko ritel kecil. Keunggulan utamanya adalah **Draft Transaksi**: pelanggan yang sudah berada di antrean kasir tetapi masih ingin mencari barang dapat "menitipkan" keranjangnya sebagai draft dalam satu klik, sehingga pelanggan di belakangnya bisa langsung dilayani. Draft dapat dilanjutkan kembali oleh kasir mana pun, kapan saja, selama belum kedaluwarsa.

Aplikasi ini dibuat untuk keperluan tugas skripsi klien, sehingga selain berfungsi penuh juga harus menghasilkan data yang bisa dianalisis (misalnya jumlah dan durasi draft) untuk bab pengujian.

## 2. Tujuan dan Metrik Keberhasilan

| # | Tujuan | Indikator |
|---|---|---|
| G1 | Kasir dapat menyelesaikan transaksi cepat | Transaksi 3 item tunai dapat diselesaikan dalam ≤ 6 klik dari halaman kasir |
| G2 | Antrean tidak terblokir | Menyimpan keranjang ke draft ≤ 2 klik dan keranjang langsung kosong untuk pelanggan berikutnya |
| G3 | Data stok akurat | Stok hanya berubah saat transaksi selesai, void, penambahan, atau penyesuaian, dan semuanya tercatat di riwayat stok |
| G4 | Data skripsi tersedia | Laporan Draft menampilkan jumlah dibuat, diselesaikan, dibuang, kedaluwarsa, dan rata-rata durasi draft sampai selesai |
| G5 | Aman dan siap deploy | Semua quality gate di AGENTS.md lulus; berjalan di PHP 8.2 |

## 3. Pengguna dan Role

| Role | Deskripsi | Kemampuan utama |
|---|---|---|
| `admin` | Pemilik atau manajer toko | Semua fitur: master data, stok, laporan, pengguna, pengaturan, void, log |
| `kasir` | Petugas kasir | Halaman kasir, draft, riwayat transaksi miliknya, dashboard ringkas |

Hanya dua role. Disimpan sebagai kolom `users.role`.

## 4. Ruang Lingkup

### 4.1 Dalam lingkup (MVP)

1. Autentikasi (login, logout) dan manajemen pengguna.
2. Master data: kategori, satuan, produk.
3. Manajemen stok: stok saat ini, stok masuk, penyesuaian (stock opname), riwayat pergerakan.
4. Kasir (POS): cari produk, keranjang, diskon transaksi, pajak, pembayaran, struk.
5. **Draft transaksi**: simpan, daftar, lanjutkan, edit, buang, kunci antar kasir, kedaluwarsa otomatis.
6. Riwayat transaksi, cetak ulang struk, void (admin).
7. Laporan: penjualan, produk terlaris, stok, draft. Export lewat tombol DataTables.
8. Dashboard.
9. Pengaturan toko.
10. Log aktivitas (`system_logs`).

### 4.2 Di luar lingkup

Multi-cabang, member/poin, supplier dan purchase order, split payment, retur parsial, shift kasir dan rekap kas, integrasi payment gateway, integrasi printer thermal langsung (cukup `window.print()`), mode offline/PWA, satuan desimal (kg, liter; kuantitas hanya bilangan bulat), diskon per item, multi-bahasa.

## 5. Keputusan Teknis dan Asumsi

| Topik | Keputusan |
|---|---|
| Framework | Laravel 12, PHP 8.2 (server produksi). **Dilarang** memakai sintaks atau API PHP 8.3+ |
| Composer | Tambahkan `config.platform.php = "8.2.0"` di `composer.json` agar dependency tidak melebihi PHP 8.2 |
| Database | MySQL/MariaDB untuk pengembangan dan produksi; test memakai SQLite in-memory (default `phpunit.xml`). Semua migration harus kompatibel dengan keduanya |
| Primary key | `bigint` auto-increment untuk semua tabel |
| Uang | Disimpan sebagai `unsignedBigInteger` dalam Rupiah utuh (tanpa desimal) |
| Kuantitas | Integer positif |
| View | Blade + Otika (Bootstrap 4, jQuery). Tidak memakai Livewire, Inertia, Vue, atau React |
| Asset Otika | Dimuat dari `ASSET_URL` (`https://otika.namikulo.com/assets`) lewat helper `asset()`; jangan menyalin asset ke repo |
| Storage publik | Gambar produk di `storage/app/public/products`, URL dibuat dengan `url('storage/...')`, bukan `asset()` |
| Autentikasi | Session auth bawaan Laravel tanpa starter kit (Breeze/Jetstream **tidak** dipakai); halaman login mengikuti `auth-login.html` Otika |
| Otorisasi | Middleware alias `role` (didaftarkan di `bootstrap/app.php`) + Policy `SalePolicy` |
| Validasi | `$request->validate([...])`; satu Form Request `SaleCartRequest` boleh dibuat karena dipakai ulang oleh draft dan checkout |
| Zona waktu dan locale | `APP_TIMEZONE` dan `APP_LOCALE=id` dari `.env` (default `Asia/Jakarta`, ubah sesuai lokasi toko) |
| Format Rupiah | Helper `format_rupiah(int $n): string` (contoh `Rp 12.500`), **tanpa** ekstensi `intl` |
| Notifikasi AJAX | Komponen `alert` Otika di container khusus; **tidak** menambah SweetAlert/Toastr |
| Chart | Hanya di dashboard, memakai ApexCharts bawaan Otika |
| Seeder | Admin awal dibuat dari `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env`; data demo hanya berjalan di environment `local` |

## 6. Arsitektur dan Konvensi Kode

Semua aturan di AGENTS.md berlaku. Tambahan khusus proyek:

### 6.1 Struktur direktori yang diharapkan

```text
app/
├── Enums/            SaleStatus, PaymentMethod, StockMovementType, UserRole
├── Http/
│   ├── Controllers/  Auth/LoginController, DashboardController, UserController,
│   │                 CategoryController, UnitController, ProductController,
│   │                 StockController, PosController, DraftController,
│   │                 TransactionController, ReportController,
│   │                 StoreSettingController, SystemLogController
│   ├── Middleware/   EnsureRole
│   └── Requests/     SaleCartRequest
├── Models/           User, Category, Unit, Product, StockMovement, Sale, SaleItem,
│                     NumberSequence, StoreSetting, SystemLog
├── Policies/         SalePolicy
├── Services/         SaleCalculator, SaleService, StockService, NumberGenerator, ActivityLogger
├── Console/Commands/ PruneDrafts (drafts:prune)
└── Support/helpers.php   (format_rupiah, didaftarkan lewat composer "autoload.files")
resources/views/
├── layouts/          app.blade.php, auth.blade.php
├── partials/         navbar, sidebar, footer, flash, delete-modal
├── auth/login.blade.php
├── dashboard/  users/  categories/  units/  products/  stock/
├── pos/index.blade.php
├── transactions/     index, show, receipt
├── reports/          sales, products, stock, drafts
├── settings/edit.blade.php
├── system-logs/index.blade.php
└── errors/           403, 404, 419, 500 (mengikuti errors-*.html Otika)
public/js/pos.js          (script halaman kasir; bukan vendor)
public/css/pos.css        (override khusus halaman kasir)
docs/DECISIONS.md
```

### 6.2 Pembagian tanggung jawab

- **Controller tipis**: validasi, otorisasi, panggil service, kembalikan response.
- **`SaleCalculator`**: fungsi murni untuk subtotal, diskon, pajak, total, kembalian (mudah diuji unit).
- **`SaleService`**: `saveDraft()`, `updateDraft()`, `resumeDraft()`, `releaseDraft()`, `discardDraft()`, `checkout()`, `void()`. Semua operasi multi-tabel dibungkus `DB::transaction`.
- **`StockService`**: satu-satunya tempat yang mengubah `products.stock` dan menulis `stock_movements`.
- **`NumberGenerator`**: nomor invoice dan draft aman terhadap konkurensi memakai `number_sequences` + `lockForUpdate`.
- **`ActivityLogger`**: menulis `system_logs` dan membersihkan password, token, dan secret dari payload.

### 6.3 Aturan keamanan yang wajib untuk POS

1. **Klien tidak pernah menentukan harga.** Payload keranjang hanya berisi `product_id` dan `quantity`; server membaca harga dari database.
2. Semua total dihitung ulang di server.
3. Konten user (nama produk, label draft) di-escape saat dirender di JavaScript: gunakan `.text()` atau `textContent`, bukan `innerHTML` mentah.
4. Login dibatasi (throttle) 5 percobaan per menit per email+IP. Session di-regenerate setelah login.
5. User dengan `is_active = false` tidak bisa login dan sesi aktifnya ditolak oleh middleware.
6. Upload gambar produk: hanya `jpg, jpeg, png, webp`, maksimal 2 MB, nama file di-generate ulang.

## 7. Navigasi dan Hak Akses

### 7.1 Sidebar (Otika)

| Grup | Menu | Route name | admin | kasir |
|---|---|---|---|---|
| MAIN | Dashboard | `dashboard` | ✔ | ✔ |
| KASIR | Kasir (POS) | `pos.index` | ✔ | ✔ |
| KASIR | Riwayat Transaksi | `transactions.index` | ✔ (semua) | ✔ (miliknya) |
| MASTER DATA | Produk | `products.index` | ✔ | ✘ |
| MASTER DATA | Kategori | `categories.index` | ✔ | ✘ |
| MASTER DATA | Satuan | `units.index` | ✔ | ✘ |
| INVENTORI | Stok | `stock.index` | ✔ | ✘ |
| INVENTORI | Riwayat Stok | `stock.movements` | ✔ | ✘ |
| LAPORAN | Penjualan | `reports.sales` | ✔ | ✘ |
| LAPORAN | Produk Terlaris | `reports.products` | ✔ | ✘ |
| LAPORAN | Stok | `reports.stock` | ✔ | ✘ |
| LAPORAN | Draft Transaksi | `reports.drafts` | ✔ | ✘ |
| PENGATURAN | Pengguna | `users.index` | ✔ | ✘ |
| PENGATURAN | Pengaturan Toko | `settings.edit` | ✔ | ✘ |
| PENGATURAN | Log Aktivitas | `system-logs.index` | ✔ | ✘ |

Menu disembunyikan lewat `@can`/pengecekan role **dan** route tetap diproteksi di server.

### 7.2 Matriks aksi pada transaksi

| Aksi | admin | kasir |
|---|---|---|
| Membuat transaksi dan draft | ✔ | ✔ |
| Melihat dan melanjutkan draft (semua draft toko) | ✔ | ✔ |
| Membuang draft | ✔ (semua) | ✔ (hanya draft yang ia buat) |
| Melihat detail dan cetak ulang struk | ✔ (semua) | ✔ (miliknya) |
| Void transaksi selesai | ✔ | ✘ |

## 8. Model Data

Semua tabel memiliki `created_at` dan `updated_at` kecuali dinyatakan lain. Nama kolom berbahasa Inggris.

### 8.1 `users` (ubah migration bawaan)

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string(100) | |
| email | string unique | dipakai untuk login |
| password | string | hashed |
| role | string(20) | `admin` atau `kasir`, default `kasir` |
| is_active | boolean | default true |
| remember_token | | bawaan |

Tabel bawaan `password_reset_tokens` dan `sessions` boleh dipertahankan; fitur lupa password tidak dibuat.

### 8.2 `categories`

`id`, `name` string(100) **unique**.

### 8.3 `units`

`id`, `name` string(30) **unique** (contoh: pcs, box, botol).

### 8.4 `products`

| Kolom | Tipe | Catatan |
|---|---|---|
| category_id | FK categories | restrict on delete |
| unit_id | FK units | restrict on delete |
| sku | string(50) unique | |
| barcode | string(64) nullable unique | |
| name | string(150) | index |
| cost_price | unsignedBigInteger | default 0 |
| sell_price | unsignedBigInteger | |
| stock | integer | default 0; hanya diubah oleh `StockService` |
| min_stock | integer | default 0 |
| image_path | string nullable | |
| is_active | boolean | default true |

Tanpa soft delete. Produk yang sudah punya `sale_items` atau `stock_movements` **tidak boleh dihapus** (tampilkan pesan agar dinonaktifkan).

### 8.5 `stock_movements`

| Kolom | Tipe | Catatan |
|---|---|---|
| product_id | FK products | restrict |
| user_id | FK users | pelaku |
| type | string(20) | `initial`, `in`, `sale`, `void_return`, `adjustment` |
| quantity_change | integer | bertanda (+ atau −) |
| stock_before | integer | |
| stock_after | integer | |
| sale_id | FK sales nullable | untuk `sale` dan `void_return` |
| note | string(255) nullable | |

### 8.6 `sales` (transaksi dan draft dalam satu tabel)

| Kolom | Tipe | Catatan |
|---|---|---|
| status | string(20) | `draft`, `completed`, `voided`, `discarded`; index |
| draft_number | string(30) nullable unique | contoh `DRF-20260921-001` |
| invoice_number | string(30) nullable unique | contoh `INV-20260921-0001`, terisi saat completed |
| label | string(100) nullable | catatan pelanggan pada draft, misalnya "Ibu baju merah" |
| user_id | FK users | pembuat awal |
| completed_by | FK users nullable | kasir yang menyelesaikan |
| locked_by | FK users nullable | kasir yang sedang membuka draft |
| locked_at | timestamp nullable | |
| subtotal | unsignedBigInteger | default 0 |
| discount_type | string(10) nullable | `nominal` atau `percent` |
| discount_value | unsignedBigInteger | default 0 (nominal Rupiah atau persen 0–100) |
| discount_amount | unsignedBigInteger | hasil hitung |
| tax_percent | decimal(5,2) | snapshot dari pengaturan |
| tax_amount | unsignedBigInteger | |
| total | unsignedBigInteger | |
| payment_method | string(20) nullable | `cash`, `qris`, `transfer`, `debit` |
| paid_amount | unsignedBigInteger nullable | |
| change_amount | unsignedBigInteger nullable | |
| payment_reference | string(100) nullable | no. referensi non-tunai |
| note | string(255) nullable | |
| drafted_at | timestamp nullable | pertama kali disimpan sebagai draft |
| completed_at | timestamp nullable | index |
| voided_at, voided_by (FK users nullable), void_reason (string 255 nullable) | | |
| discarded_at | timestamp nullable | |
| discard_reason | string(20) nullable | `manual` atau `expired` |

Index tambahan: `(status, completed_at)`.

### 8.7 `sale_items`

`id`, `sale_id` FK cascade, `product_id` FK restrict, snapshot `product_name`, `sku`, `unit_name`, `quantity` int, `unit_price` unsignedBigInteger, `cost_price` unsignedBigInteger (snapshot untuk laba), `subtotal` unsignedBigInteger. Unique `(sale_id, product_id)`: satu produk satu baris; menambah produk yang sama menaikkan `quantity`.

### 8.8 `number_sequences`

`id`, `type` string(10) (`invoice`/`draft`), `date` date, `last_number` unsignedInteger. Unique `(type, date)`.

### 8.9 `store_settings` (satu baris)

| Kolom | Default |
|---|---|
| store_name | "antre-in" |
| address, phone | nullable |
| receipt_footer | "Terima kasih atas kunjungan Anda" |
| tax_percent decimal(5,2) | 0 |
| draft_expire_hours unsignedInteger | 24 |
| max_active_drafts unsignedInteger | 20 |

Akses lewat `StoreSetting::current()` (buat baris default jika belum ada, di-cache per request).

### 8.10 `system_logs`

`id`, `user_id` FK nullable, `action` (`created`, `updated`, `deleted`, `login`, `logout`, `draft_saved`, `draft_resumed`, `draft_discarded`, `checkout`, `void`, `stock_adjusted`, `stock_received`), `table_name`, `record_id` nullable, `method`, `url`, `ip_address`, `old_values` json nullable, `new_values` json nullable. Password, token, secret dan file biner tidak boleh masuk payload.

## 9. Aturan Bisnis

### 9.1 Perhitungan (dihitung di server oleh `SaleCalculator`)

```text
subtotal        = Σ (quantity × unit_price)
discount_amount = nominal : min(discount_value, subtotal)
                  percent : floor(subtotal × discount_value / 100)   (discount_value 0..100)
taxable         = subtotal − discount_amount
tax_amount      = round(taxable × tax_percent / 100)
total           = taxable + tax_amount
cash            : paid_amount ≥ total ; change_amount = paid_amount − total
non-cash        : paid_amount = total ; change_amount = 0
```

### 9.2 Stok

1. Draft **tidak** mengurangi atau memesan (reserve) stok.
2. Stok berkurang hanya saat checkout berhasil (`type = sale`).
3. Saat checkout: dalam satu transaksi database, kunci baris produk dengan `lockForUpdate` **berurutan menurut `product_id`** (mencegah deadlock), cek `stock ≥ quantity` dan `is_active`, lalu kurangi. Jika ada yang kurang, batalkan semua dan kembalikan error 422 per item.
4. Stok tidak boleh negatif.
5. Void mengembalikan stok (`type = void_return`).
6. Stok menipis: `stock <= min_stock` (dan `min_stock > 0`).

### 9.3 Penomoran

- Invoice: `INV-YYYYMMDD-NNNN` (reset harian, minimal 4 digit).
- Draft: `DRF-YYYYMMDD-NNN` (reset harian, minimal 3 digit).
- Dibuat oleh `NumberGenerator` dalam transaksi database dengan `lockForUpdate` pada baris `number_sequences`.

### 9.4 Siklus status `sales`

```text
            simpan draft                checkout
(keranjang) ───────────────▶ draft ───────────────▶ completed ──void(admin)──▶ voided
    │                          │  ▲
    │ checkout langsung        │  └─ resume / simpan ulang (nomor draft tetap)
    └──────────────────────────┼───────────────▶ completed
                               └─ buang manual / kedaluwarsa ─▶ discarded
```

Draft yang diselesaikan tetap menyimpan `draft_number` dan `drafted_at` (untuk analisis durasi), lalu mendapat `invoice_number` dan `completed_at`.

## 10. Spesifikasi Fitur

Prioritas: **P0** wajib MVP, **P1** sebaiknya ada, **P2** opsional jika waktu ada.

### 10.1 Autentikasi

| ID | Prioritas | Kebutuhan |
|---|---|---|
| AUTH-1 | P0 | Halaman login memakai layout auth Otika (tanpa sidebar), field email dan password, old(), pesan error, dan checkbox "ingat saya" |
| AUTH-2 | P0 | Logout via `POST` dengan CSRF |
| AUTH-3 | P0 | Setelah login, admin dan kasir diarahkan ke `dashboard`; kasir juga bisa langsung ke `pos.index` lewat menu |
| AUTH-4 | P0 | User nonaktif ditolak dengan pesan jelas; throttle sesuai §6.3 |
| AUTH-5 | P1 | Halaman error 403/404/419/500 mengikuti template error Otika |

**AC:** login salah menampilkan error tanpa membocorkan apakah email terdaftar; akses `/pos` tanpa login diarahkan ke `/login`; kasir yang membuka URL admin mendapat 403.

### 10.2 Pengguna (admin)

| ID | Prioritas | Kebutuhan |
|---|---|---|
| USR-1 | P0 | CRUD pengguna memakai **modal** create/edit dan modal delete; listing DataTables client-side |
| USR-2 | P0 | Field: nama, email (unik), role, status aktif, password (wajib saat create, opsional saat edit) |
| USR-3 | P0 | Admin tidak dapat menghapus atau menonaktifkan dirinya sendiri |
| USR-4 | P0 | Pengguna yang sudah punya transaksi atau log tidak dapat dihapus; sarankan menonaktifkan |

### 10.3 Master data (admin)

| ID | Prioritas | Kebutuhan |
|---|---|---|
| MST-1 | P0 | Kategori dan Satuan: CRUD modal; nama unik; tidak bisa dihapus jika dipakai produk |
| MST-2 | P0 | Produk: CRUD modal dengan field kategori, satuan, SKU, barcode, nama, harga beli, harga jual, stok awal (hanya saat create), stok minimum, gambar, status aktif |
| MST-3 | P0 | Stok awal > 0 saat create membuat `stock_movements` bertipe `initial` |
| MST-4 | P0 | Pada edit, kolom stok read-only (perubahan lewat modul Stok) |
| MST-5 | P0 | Kolom listing produk: gambar kecil, SKU, nama, kategori, harga jual, stok (badge merah jika menipis), status, aksi |
| MST-6 | P1 | Harga jual < harga beli menampilkan peringatan (bukan error) |

**Pola modal (wajib konsisten untuk semua master):** form create dan edit memakai satu modal Bootstrap 4 dengan `_method` PUT saat edit. Submit normal + redirect + flash. Saat validasi gagal, halaman dirender ulang dengan `old()`, error per field (`is-invalid`, `invalid-feedback`), dan **modal terbuka otomatis**: gunakan input hidden `_form` (`create` atau `edit`) dan `_id`, lalu script kecil membuka modal yang sesuai dan mengatur `action` form. Tombol delete menyimpan `data-id` dan `data-name` dan mengisi modal delete (form `@method('DELETE')`).

### 10.4 Stok (admin)

| ID | Prioritas | Kebutuhan |
|---|---|---|
| STK-1 | P0 | Halaman **Stok**: tabel produk (SKU, nama, kategori, stok, min stok, status "Menipis"/"Aman"/"Habis") dengan filter kategori dan filter "hanya menipis" |
| STK-2 | P0 | **Stok Masuk** (modal): pilih produk, jumlah > 0, catatan, opsional harga beli baru (jika diisi, memperbarui `cost_price`). Menulis movement `in` |
| STK-3 | P0 | **Penyesuaian** (modal / stock opname): pilih produk, isi **stok aktual**, catatan wajib; selisih dihitung otomatis, movement `adjustment` |
| STK-4 | P0 | **Riwayat Stok**: filter rentang tanggal (default 30 hari terakhir), produk, tipe; kolom waktu, produk, tipe, perubahan, sebelum, sesudah, pelaku, referensi transaksi, catatan |
| STK-5 | P1 | Ekspor tabel lewat tombol DataTables (copy, csv, excel, pdf, print) |

Pemilihan produk pada modal boleh memakai `.select2` (uji di dalam modal; atur `dropdownParent`).

### 10.5 Kasir (POS) — halaman `pos.index`

| ID | Prioritas | Kebutuhan |
|---|---|---|
| POS-1 | P0 | Kolom pencarian produk (nama, SKU, barcode) dengan debounce 300 ms; hasil dari `GET /pos/products` (server-side, 24 per halaman) |
| POS-2 | P0 | Enter pada pencarian: jika cocok persis dengan SKU/barcode, produk langsung masuk keranjang (mendukung barcode scanner keyboard-wedge) |
| POS-3 | P0 | Filter kategori (tombol/pill) dan grid produk (nama, harga, stok). Produk stok 0 tampil redup dan tidak bisa diklik |
| POS-4 | P0 | Keranjang: daftar item, tombol − / + dan input qty, hapus item, subtotal per item. Qty dibatasi ke stok yang tersedia (batasan lunak di klien) |
| POS-5 | P0 | Diskon transaksi: pilih `nominal` atau `percent`, isi nilai; total ter-update langsung |
| POS-6 | P0 | Ringkasan: Subtotal, Diskon, Pajak (dari pengaturan), **Total** |
| POS-7 | P0 | Tombol **Bayar** membuka modal pembayaran: metode (tunai/QRIS/transfer/debit), jumlah bayar (tunai), tombol nominal cepat (uang pas, 20.000, 50.000, 100.000), kembalian real-time, referensi (non-tunai, opsional) |
| POS-8 | P0 | Setelah bayar sukses: modal sukses berisi invoice, total, kembalian, tombol **Cetak Struk** dan **Transaksi Baru** |
| POS-9 | P0 | Tombol **Kosongkan** (dengan konfirmasi) |
| POS-10 | P1 | Shortcut keyboard: `F2` fokus pencarian, `F4` simpan draft, `F8` buka daftar draft, `F9` bayar |
| POS-11 | P2 | Keranjang yang sedang dikerjakan disimpan di `sessionStorage` agar tidak hilang saat refresh tidak sengaja |
| POS-12 | P0 | State: loading (spinner saat mencari), empty (produk tidak ditemukan / keranjang kosong), error (pesan alert), success |

Kegagalan checkout karena stok (422) menampilkan pesan per item di keranjang dan keranjang **tidak dikosongkan**.

### 10.6 Draft Transaksi — fitur unggulan

| ID | Prioritas | Kebutuhan |
|---|---|---|
| DFT-1 | P0 | Tombol **Simpan ke Draft** (kuning, samping tombol Bayar) aktif jika keranjang tidak kosong. Klik membuka modal kecil: input **label** opsional (placeholder "mis. Ibu baju merah") dan tombol Simpan |
| DFT-2 | P0 | Setelah simpan: draft tersimpan di server (`status = draft`, `drafted_at = now`, nomor `DRF-...`), keranjang **langsung kosong**, muncul alert sukses berisi nomor draft, fokus kembali ke pencarian |
| DFT-3 | P0 | Tombol **Draft (n)** dengan badge jumlah draft aktif. Jumlah diperbarui setelah setiap aksi dan lewat polling ringan tiap 30 detik (`GET /pos/drafts?count_only=1`) |
| DFT-4 | P0 | Klik tombol Draft membuka modal **Daftar Draft**: nomor, label, jumlah item, total (perkiraan), waktu dibuat (relatif, contoh "12 menit lalu"), pembuat, status kunci; aksi **Lanjutkan** dan **Buang**. Ada empty state jika kosong |
| DFT-5 | P0 | **Lanjutkan**: draft dimuat ke keranjang dengan harga dan stok **terbaru**; jika ada perubahan harga, produk nonaktif, atau stok kurang, tampil peringatan per item dan qty disesuaikan ke stok tersedia (item nonaktif/stok 0 dikeluarkan dan dilaporkan). Keranjang harus kosong sebelum melanjutkan; jika tidak, tampil konfirmasi "Simpan keranjang saat ini ke draft dulu?" |
| DFT-6 | P0 | Draft yang sedang dilanjutkan membawa `draft_id`. **Simpan ke Draft** lagi memperbarui draft yang sama (nomor tetap) dan melepas kunci. **Bayar** mengonversi draft itu menjadi transaksi selesai (bukan membuat baris baru) |
| DFT-7 | P0 | **Kunci draft**: saat dilanjutkan, `locked_by` dan `locked_at` diisi. Kasir lain melihat "Sedang dibuka oleh {nama}" dan tombol Lanjutkan nonaktif. Kunci dianggap basi setelah 15 menit (`config('pos.draft_lock_minutes')`), lalu boleh diambil alih. Kunci dilepas saat simpan ulang, bayar, buang, atau tombol Kosongkan |
| DFT-8 | P0 | **Kosongkan** pada draft yang sedang dilanjutkan: konfirmasi "Draft tetap tersimpan", lepas kunci, kosongkan keranjang (draft tidak berubah dari versi tersimpan terakhir) |
| DFT-9 | P0 | **Buang** draft (konfirmasi): `status = discarded`, `discard_reason = manual`, `discarded_at = now`. Data tidak dihapus permanen (untuk analisis). Draft yang terkunci aktif oleh kasir lain tidak bisa dibuang |
| DFT-10 | P0 | Batas draft aktif (`max_active_drafts`, default 20): melebihi batas ditolak dengan 422 dan pesan jelas |
| DFT-11 | P0 | **Kedaluwarsa otomatis**: command `drafts:prune` (dijadwalkan tiap 15 menit di `routes/console.php`) mengubah draft dengan `drafted_at` lebih lama dari `draft_expire_hours` dan tidak terkunci aktif menjadi `discarded` dengan `discard_reason = expired`. Command yang sama melepas kunci basi |
| DFT-12 | P0 | Draft **tidak** mengurangi stok dan tidak muncul di laporan penjualan |
| DFT-13 | P0 | Draft dapat dilihat semua kasir (dibagikan per toko) |
| DFT-14 | P1 | Laporan Draft (lihat §10.8) |

**Acceptance criteria inti (Given/When/Then):**

1. *Given* keranjang berisi 3 item, *when* kasir klik Simpan ke Draft lalu Simpan, *then* keranjang kosong, badge Draft bertambah 1, stok produk tidak berubah.
2. *Given* ada draft milik kasir A, *when* kasir B membuka daftar draft dan klik Lanjutkan, *then* keranjang B terisi dan draft terkunci atas nama B; kasir A melihat status "Sedang dibuka oleh B".
3. *Given* draft berisi produk yang harganya berubah, *when* dilanjutkan, *then* harga baru dipakai dan peringatan perubahan harga tampil.
4. *Given* draft berisi qty 5 padahal stok kini 2, *when* dilanjutkan, *then* qty menjadi 2 dan peringatan tampil; jika stok 0 item dikeluarkan.
5. *Given* draft dilanjutkan lalu dibayar, *then* baris `sales` yang sama berubah menjadi `completed` dengan `invoice_number` terisi, `draft_number` dan `drafted_at` tetap ada, dan stok berkurang.
6. *Given* draft berumur lebih dari `draft_expire_hours`, *when* `drafts:prune` berjalan, *then* status menjadi `discarded` dengan alasan `expired`.
7. *Given* dua kasir membayar produk yang sama dengan stok tersisa 1 secara hampir bersamaan, *then* hanya satu yang sukses dan satunya mendapat error stok.

### 10.7 Riwayat transaksi dan void

| ID | Prioritas | Kebutuhan |
|---|---|---|
| TRX-1 | P0 | Listing DataTables: invoice, waktu, kasir, item, metode bayar, total, status. Filter rentang tanggal dan status (admin: Selesai, Draft, Void, Dibuang; kasir: Selesai dan Void miliknya) |
| TRX-2 | P0 | Halaman detail: info transaksi, tabel item, rincian pembayaran, dan riwayat perubahan status |
| TRX-3 | P0 | **Struk** (`transactions.receipt`): halaman ringkas lebar ~80 mm, berisi nama toko, alamat, telepon, nomor invoice, waktu, kasir, item, subtotal, diskon, pajak, total, bayar, kembalian, footer. Ada CSS `@media print` dan tombol Cetak (`window.print()`); tanpa sidebar/navbar |
| TRX-4 | P0 | **Void** (admin, modal): wajib isi alasan; status menjadi `voided`, stok dikembalikan dengan movement `void_return`, tercatat di log. Transaksi yang sudah void tidak bisa di-void lagi |
| TRX-5 | P1 | Admin dapat membuang draft dari halaman ini (status Draft) |

### 10.8 Laporan (admin)

Semua laporan: filter `start_date` dan `end_date` (komponen `.daterange`, default bulan berjalan), ringkasan KPI di atas (card statistic), tabel DataTables client-side dengan tombol export (copy, csv, excel, pdf, print). Hanya transaksi `completed` yang dihitung kecuali disebutkan lain.

| ID | Prioritas | Laporan | Isi |
|---|---|---|---|
| RPT-1 | P0 | Penjualan | Per hari: jumlah transaksi, subtotal, diskon, pajak, total. KPI: total omzet, jumlah transaksi, rata-rata per transaksi, laba kotor (Σ (unit_price − cost_price) × qty − diskon) |
| RPT-2 | P0 | Produk Terlaris | Per produk: qty terjual, omzet, laba kotor; urut qty terbanyak |
| RPT-3 | P0 | Stok | Snapshot stok saat ini per produk, nilai persediaan (stok × harga beli), penanda menipis |
| RPT-4 | P1 | Draft Transaksi | Dalam rentang `drafted_at`: jumlah draft dibuat, diselesaikan, dibuang manual, kedaluwarsa, masih aktif; rata-rata dan median durasi draft → selesai (menit); daftar draft (nomor, label, pembuat, dibuat, status, durasi). Ini data utama untuk bab pengujian skripsi |

### 10.9 Dashboard

| ID | Prioritas | Kebutuhan |
|---|---|---|
| DSH-1 | P0 | **Admin**: KPI (`card-statistic-1`): omzet hari ini, transaksi hari ini, draft aktif, produk stok menipis |
| DSH-2 | P0 | **Admin**: grafik penjualan 7 hari terakhir (ApexCharts, garis), tabel 5 produk terlaris bulan ini, daftar produk stok menipis (maks. 10) |
| DSH-3 | P0 | **Kasir**: KPI transaksi dan omzet miliknya hari ini, jumlah draft aktif, tombol pintas ke Kasir. Tanpa grafik |
| DSH-4 | P0 | State empty untuk setiap widget |

### 10.10 Pengaturan toko (admin)

`settings.edit` / `settings.update`: nama toko, alamat, telepon, footer struk, persentase pajak (0–100), lama kedaluwarsa draft (jam, 1–168), maksimum draft aktif (1–100). Perubahan pajak hanya memengaruhi transaksi baru (snapshot di `sales`).

### 10.11 Log aktivitas (P1, admin)

Halaman `system-logs.index`: tabel DataTables (waktu, pengguna, aksi, tabel, record, IP) dan modal detail untuk melihat snapshot old/new. Pencatatan mengikuti daftar aksi pada §8.10. Untuk dataset besar, batasi tampilan ke 30 hari terakhir dengan filter tanggal.

## 11. Kontrak Route dan API

Semua route di dalam middleware `auth` (kecuali login). Penamaan mengikuti konvensi resource AGENTS.md.

### 11.1 Route halaman

| Method | URI | Nama | Akses |
|---|---|---|---|
| GET | `/login` | `login` | tamu |
| POST | `/login` | `login.attempt` | tamu |
| POST | `/logout` | `logout` | auth |
| GET | `/` | redirect ke `dashboard` | auth |
| GET | `/dashboard` | `dashboard` | auth |
| resource | `/users` (`except show`) | `users.*` | admin |
| resource | `/categories` (`except show`) | `categories.*` | admin |
| resource | `/units` (`except show`) | `units.*` | admin |
| resource | `/products` (`except show`) | `products.*` | admin |
| GET | `/stock` | `stock.index` | admin |
| POST | `/stock/receive` | `stock.receive` | admin |
| POST | `/stock/adjust` | `stock.adjust` | admin |
| GET | `/stock/movements` | `stock.movements` | admin |
| GET | `/pos` | `pos.index` | auth |
| GET | `/transactions` | `transactions.index` | auth |
| GET | `/transactions/{sale}` | `transactions.show` | auth + `SalePolicy@view` |
| GET | `/transactions/{sale}/receipt` | `transactions.receipt` | auth + `SalePolicy@view` |
| POST | `/transactions/{sale}/void` | `transactions.void` | admin |
| GET | `/reports/sales` | `reports.sales` | admin |
| GET | `/reports/products` | `reports.products` | admin |
| GET | `/reports/stock` | `reports.stock` | admin |
| GET | `/reports/drafts` | `reports.drafts` | admin |
| GET/PUT | `/settings` | `settings.edit` / `settings.update` | admin |
| GET | `/system-logs` | `system-logs.index` | admin |

### 11.2 Endpoint JSON kasir

Semua memakai header `X-CSRF-TOKEN` (dari Blade), `Accept: application/json`. Format error validasi: `{ "message": "...", "errors": { "field": ["pesan"] } }` (status 422).

**`GET /pos/products?q=&category_id=&page=`** (`pos.products`)

```json
{ "data": [ { "id": 1, "sku": "KPI-001", "barcode": "899...", "name": "Kopi Susu", "category": "Minuman",
              "unit": "cup", "price": 18000, "stock": 12, "image_url": "https://.../storage/products/x.jpg" } ],
  "meta": { "current_page": 1, "last_page": 3, "total": 61 } }
```

Hanya produk `is_active = true`; pencarian `LIKE` pada nama, SKU, barcode; urut nama.

**Payload keranjang** (dipakai draft dan checkout, divalidasi `SaleCartRequest`):

```json
{ "items": [ { "product_id": 1, "quantity": 2 } ],
  "discount_type": "percent", "discount_value": 10 }
```

Aturan: `items` minimal 1, `product_id` exists dan unik dalam array, `quantity` integer 1–9999, `discount_type` nullable in `nominal,percent`, `discount_value` integer ≥ 0 (percent ≤ 100).

**`POST /pos/drafts`** (`pos.drafts.store`): payload keranjang + `label` (opsional, maks 100) → `201`

```json
{ "message": "Draft DRF-20260921-001 tersimpan.",
  "data": { "id": 15, "draft_number": "DRF-20260921-001", "label": "Ibu baju merah", "total": 54000 },
  "active_drafts": 3 }
```

**`PUT /pos/drafts/{sale}`** (`pos.drafts.update`): sama seperti store; hanya jika `status = draft` dan (tidak terkunci atau terkunci oleh user ini). Melepas kunci. `409` jika terkunci oleh user lain.

**`GET /pos/drafts`** (`pos.drafts.index`): `?count_only=1` → `{ "count": 3 }`; tanpa parameter:

```json
{ "count": 3,
  "data": [ { "id": 15, "draft_number": "DRF-...", "label": "Ibu baju merah", "items_count": 3,
              "total": 54000, "created_by": "Sari", "drafted_at": "2026-09-21T10:15:00+07:00",
              "drafted_human": "12 menit lalu", "locked_by": null, "can_resume": true, "can_discard": true } ] }
```

**`POST /pos/drafts/{sale}/resume`** (`pos.drafts.resume`) → `200` dan mengunci draft:

```json
{ "message": "Draft dimuat.",
  "data": { "draft": { "id": 15, "draft_number": "DRF-...", "label": "...", "discount_type": "percent", "discount_value": 10 },
            "items": [ { "product_id": 1, "name": "Kopi Susu", "sku": "KPI-001", "unit": "cup",
                         "quantity": 2, "unit_price": 18000, "stock": 12 } ] },
  "warnings": [ "Harga Kopi Susu berubah dari Rp 17.000 menjadi Rp 18.000.",
                "Stok Roti Tawar hanya 1, jumlah disesuaikan." ] }
```

`409` bila terkunci aktif oleh user lain: `{ "message": "Draft sedang dibuka oleh Budi." }`.

**`POST /pos/drafts/{sale}/release`** (`pos.drafts.release`): melepas kunci tanpa mengubah isi → `200`.

**`DELETE /pos/drafts/{sale}`** (`pos.drafts.destroy`): membuang draft (`discarded`) → `200` `{ "message": "...", "active_drafts": 2 }`.

**`POST /pos/checkout`** (`pos.checkout`): payload keranjang + berikut → `201`

```json
{ "draft_id": 15, "payment_method": "cash", "paid_amount": 100000, "payment_reference": null, "note": null }
```

```json
{ "message": "Transaksi berhasil.",
  "data": { "id": 88, "invoice_number": "INV-20260921-0007", "total": 54000,
            "paid_amount": 100000, "change_amount": 46000,
            "receipt_url": "https://.../transactions/88/receipt" } }
```

Validasi: `payment_method` in `cash,qris,transfer,debit`; `paid_amount` wajib untuk tunai dan ≥ total (dicek di server setelah kalkulasi); `draft_id` jika ada harus draft yang valid dan (tidak terkunci atau terkunci oleh user ini). Error stok: `422` dengan `errors` berkunci `items.{index}.quantity`.

## 12. Spesifikasi UI

Seluruh halaman mengikuti struktur Otika di AGENTS.md dan design.md (Bootstrap 4: `mr-*`, `ml-*`, `data-toggle`, `data-dismiss`; **dilarang** `data-bs-*`, `me-*`, `ms-*`, `btn-close`, Metronic).

### 12.1 Layout dasar

- `layouts/app.blade.php`: `<body class="light light-sidebar theme-white">`, `.loader`, `#app > .main-wrapper.main-wrapper-1`, `partials.navbar`, `partials.sidebar`, `.main-content` (`@yield('content')`), `partials.footer`. Stack `styles` di `<head>` dan `scripts` sebelum `</body>` dengan urutan dependency dari design.md §13 (`app.min.js` → plugin halaman → page config → `scripts.js` → `custom.js` → script halaman).
- `layouts/auth.blade.php`: tanpa sidebar, mengikuti `auth-login.html`.
- Asset lewat `asset('css/app.min.css')` dst.; `ASSET_URL` di `.env`. Favicon dan logo dari `img/favicon.ico` dan `img/logo.png`.
- `partials/flash.blade.php`: menampilkan `session('success')`, `session('error')` sebagai `alert alert-success|danger alert-has-icon` yang dapat ditutup (`data-dismiss="alert"`).
- Judul halaman, breadcrumb (`section-header-breadcrumb`), dan menu aktif (`active`) diatur per halaman.
- Setiap Blade yang memuat plugin memakai `@push` sehingga plugin tidak dimuat ganda.

### 12.2 Wireframe halaman Kasir

```text
┌─ section-header: Kasir ────────────────────────────────────────────────────┐
│ ┌── col-lg-7 ──────────────────────────┐ ┌── col-lg-5 ────────────────────┐ │
│ │ [🔍 Cari nama / SKU / scan barcode  ]│ │ Keranjang            [Kosongkan]│ │
│ │ (Semua)(Minuman)(Makanan)(Snack)...  │ │ ┌────────────────────────────┐ │ │
│ │ ┌────┐ ┌────┐ ┌────┐ ┌────┐          │ │ │ Kopi Susu   [-] 2 [+]  36k │ │ │
│ │ │prod│ │prod│ │prod│ │prod│          │ │ │ Roti Tawar  [-] 1 [+]  18k │ │ │
│ │ │Rp  │ │Rp  │ │Rp  │ │Rp  │          │ │ └────────────────────────────┘ │ │
│ │ │Stok│ │Stok│ │Stok│ │Stok│          │ │ Diskon: (Nominal|Persen) [   ] │ │
│ │ └────┘ └────┘ └────┘ └────┘          │ │ Subtotal              Rp 54.000│ │
│ │            ...                       │ │ Diskon               −Rp  5.400│ │
│ │ [‹ Sebelumnya]        [Berikutnya ›] │ │ Pajak (0%)                Rp 0 │ │
│ └──────────────────────────────────────┘ │ TOTAL                Rp 48.600 │ │
│                                          │ [Draft (3)] [Simpan Draft][Bayar]│ │
│                                          └────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

Detail:

- Grid produk memakai `card` kecil; area grid dan keranjang boleh `overflow-y: auto` dengan tinggi maksimum `calc(100vh - ...)` **di dalam** `pos.css` (override khusus halaman, bukan layout global).
- Di layar < 992px kolom bertumpuk; tombol aksi keranjang dibuat sticky di bawah (`position: sticky; bottom: 0`) agar tetap terjangkau di ponsel/tablet.
- Tombol: **Simpan Draft** `btn btn-warning btn-icon icon-left` (ikon `fas fa-pause`), **Bayar** `btn btn-success btn-lg`, **Draft (n)** `btn btn-outline-primary` dengan `<span class="badge badge-primary">`.
- Modal: `#modalSaveDraft`, `#modalDraftList`, `#modalPayment`, `#modalPaymentSuccess`, `#modalConfirm` (konfirmasi generik). Semua Bootstrap 4.
- Kontainer notifikasi: `#pos-alerts` di atas grid (alert Otika, hilang otomatis setelah 5 detik kecuali error).
- Script `public/js/pos.js` dibungkus IIFE, aman jika elemen target tidak ada, mengambil konfigurasi (URL endpoint, CSRF token, pajak) dari atribut `data-*` pada elemen root `#pos-app` yang dirender Blade. State keranjang berupa objek JavaScript tunggal dengan fungsi `render()`.

### 12.3 Halaman lain

- **Listing master/pengguna/produk:** `card` > `card-header` (judul + tombol Tambah di `card-header-action`) > `card-body` > `.table-responsive > table.table.table-striped` + DataTables (`pageLength: 10`, `responsive: true`).
- **Laporan:** card filter (daterange + tombol Terapkan), baris KPI `card-statistic-1`, card tabel dengan `dom: 'Bfrtip'`.
- **Struk:** halaman terpisah tanpa layout Otika penuh, CSS inline khusus struk.
- **Empty state:** ikuti pola `empty-state.html` Otika untuk tabel/daftar kosong.

## 13. Persyaratan Non-Fungsional

| Area | Persyaratan |
|---|---|
| Kompatibilitas | PHP 8.2, Laravel 12, MySQL/MariaDB, ekstensi umum saja (`mbstring`, `xml`, `curl`, `zip`, `pdo_mysql`, `openssl`, `fileinfo`); **tanpa `intl`** |
| Performa | Pencarian produk < 300 ms untuk ≤ 5.000 produk (index pada `name`, `sku`, `barcode`); tanpa N+1 (gunakan eager loading) |
| Konkurensi | Checkout, void, dan stok memakai transaksi DB + `lockForUpdate` sesuai §9.2 |
| Keamanan | Sesuai §6.3 dan AGENTS.md (CSRF, XSS, IDOR, mass assignment, upload aman, tidak ada credential di repo). Tidak ada endpoint yang mempercayai total/harga dari klien |
| Responsif | Diuji pada 375 px, 768 px, 1024 px, dan desktop; tabel lebar memakai `.table-responsive` |
| Aksesibilitas | Label untuk semua kontrol, `alt` untuk gambar, fokus keyboard terlihat, `aria-*` untuk modal dan status |
| Konsol | Tanpa error JavaScript dan tanpa 404 asset |
| Bahasa | Semua teks UI dan pesan validasi berbahasa Indonesia (sediakan `lang/id/validation.php` atau pesan kustom) |

## 14. Rencana Pengujian

Gunakan PHPUnit/Pest sesuai yang tersedia di skeleton Laravel 12 (`php artisan test`). Buat factory untuk semua model. Minimal:

| File test | Cakupan |
|---|---|
| `Unit/SaleCalculatorTest` | subtotal, diskon nominal dan persen (batas atas), pajak dengan pembulatan, kembalian, kasus diskon > subtotal |
| `Feature/AuthTest` | login sukses/gagal, user nonaktif, throttle, logout, redirect tamu |
| `Feature/RoleAccessTest` | kasir ditolak (403) di semua route admin; admin diizinkan |
| `Feature/ProductCrudTest` | create (dengan stok awal → movement `initial`), validasi unik SKU/barcode, edit tidak mengubah stok, hapus ditolak jika dipakai transaksi |
| `Feature/StockTest` | stok masuk, penyesuaian (selisih benar, catatan wajib), riwayat tercatat |
| `Feature/CheckoutTest` | sukses tunai dan non-tunai, harga dari server (abaikan harga dari klien), stok berkurang, nomor invoice berurutan, stok kurang → 422 tanpa efek samping, produk nonaktif ditolak, bayar kurang dari total ditolak |
| `Feature/DraftTest` | simpan (stok tak berubah), batas max draft, resume (peringatan harga/stok), kunci antar kasir (409), kunci basi diambil alih, update draft, checkout dari draft menjadi `completed` pada baris yang sama, buang, izin buang milik kasir lain ditolak, `drafts:prune` |
| `Feature/VoidTest` | hanya admin, stok kembali, alasan wajib, tidak bisa void dua kali |
| `Feature/ReportTest` | angka laporan penjualan/produk/draft sesuai data uji, draft tidak masuk laporan penjualan |
| `Feature/NumberGeneratorTest` | reset harian, format, tidak duplikat |

Pengujian manual (checklist di README): alur dua tab browser sebagai dua kasir berbeda pada satu draft; cetak struk; tampilan 375/768/1024 px; konsol bersih.

## 15. Fase Pengembangan

Kerjakan **berurutan**; setiap fase harus lulus quality gate AGENTS.md (`php artisan test`, `php artisan route:list`, `git diff --check`) sebelum fase berikutnya.

| Fase | Isi | Definition of Done |
|---|---|---|
| 0 | Fondasi: konfigurasi (platform PHP 8.2, `.env.example`, timezone/locale, `ASSET_URL`), layout Otika, partial, halaman login, middleware `role`, halaman error, seeder admin, `docs/DECISIONS.md` | Login/logout berfungsi, layout tampil tanpa 404 asset, akses role diuji |
| 1 | Pengguna, Kategori, Satuan, Produk (modal CRUD) | Semua CRUD lulus AC §10.2–10.3 dan test terkait |
| 2 | Modul Stok + `StockService` | AC §10.4, test stok |
| 3 | Kasir dasar: pencarian, keranjang, `SaleCalculator`, `NumberGenerator`, checkout, struk | AC §10.5 (POS-1…9, 12), test checkout |
| 4 | **Draft transaksi lengkap** + `drafts:prune` | Semua AC §10.6 lulus, termasuk uji kunci dan konkurensi |
| 5 | Riwayat, detail, void | AC §10.7 |
| 6 | Laporan, dashboard, pengaturan toko | AC §10.8–10.10 |
| 7 | Log aktivitas, shortcut keyboard, penyempurnaan, README/deployment, QA responsif, audit keamanan | Semua checklist AGENTS.md dan §13 terpenuhi |

## 16. Catatan Deployment

- `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `ASSET_URL=https://otika.namikulo.com/assets`, kredensial database, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `APP_TIMEZONE`.
- Perintah: `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan db:seed --class=AdminSeeder --force`, `php artisan storage:link`, `php artisan config:cache route:cache view:cache`.
- Scheduler (untuk `drafts:prune`): cron `* * * * * cd /path/ke/antre-in && php artisan schedule:run >> /dev/null 2>&1`.
- Pastikan versi PHP CLI di server yang menjalankan cron juga 8.2.
- Tutup akses langsung ke selain `public/` (document root mengarah ke `public/`).

## 17. Pertanyaan Terbuka dan Ide Lanjutan

Belum ada blocker; asumsi di §5 dipakai sampai klien mengonfirmasi. Sebaiknya dikonfirmasi dengan klien: (1) apakah pajak diperlukan, (2) metode pembayaran yang dipakai, (3) apakah perlu barcode scanner atau printer thermal, (4) lama kedaluwarsa draft yang diinginkan, (5) apakah kuantitas desimal (kg) dibutuhkan.

Ide lanjutan di luar MVP: member/poin, shift kasir dan rekap kas, retur parsial, split payment, diskon per item, kuantitas desimal, PWA/offline, integrasi printer thermal (ESC/POS), dan integrasi payment gateway (QRIS dinamis).
