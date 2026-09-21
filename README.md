# antre-in

Website POS berbasis Laravel 12, Blade, dan Otika Admin Template (Bootstrap 4). Frontend memakai Blade serta asset Otika langsung; tidak menggunakan Vite atau bundler frontend.

## Instalasi lokal

Persyaratan: PHP 8.2, Composer, database yang didukung Laravel, dan ekstensi PHP standar Laravel. Produksi menggunakan PHP 8.2; aplikasi tidak mensyaratkan ekstensi `intl`.

```bash
composer install
cp .env.example .env
php artisan key:generate
# isi DB_*, APP_URL, ASSET_URL, dan APP_TIMEZONE di .env
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

`AdminSeeder` membuat akun awal berikut: `admin@example.com` dan `kasir@example.com`, keduanya memakai password `Qwerty123*`. Ganti atau nonaktifkan akun tersebut sebelum deployment produksi. Asset Otika dimuat dari `ASSET_URL`, sedangkan file aplikasi di `storage/app/public` dipanggil melalui URL lokal `storage/...`.

## Scheduler dan deployment

Untuk lokal, jalankan scheduler dengan `php artisan schedule:work`. Pada server, gunakan cron berikut setiap menit:

```cron
* * * * * cd /path/antre-in && php artisan schedule:run >> /dev/null 2>&1
```

Deployment minimal:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
php artisan storage:link
php artisan optimize
```

Gunakan PHP 8.2, document root web server ke direktori `public`, isi `.env` produksi secara aman, dan pastikan izin tulis tersedia untuk `storage` serta `bootstrap/cache`.

## Testing

```bash
php artisan test
php artisan route:list
php artisan view:cache
composer validate
composer install --dry-run --no-interaction --no-scripts
git diff --check
```

Checklist manual: login admin dan dua kasir pada dua tab/browser, uji kunci dan resume draft, shortcut POS F2/F4/F8/F9, checkout dan cetak struk, void, filter laporan serta system log, asset dan console browser, lalu layout pada lebar 375px, 768px, 1024px, dan desktop.
