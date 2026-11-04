# Aplikasi Nilai

Aplikasi pengelolaan nilai siswa berbasis Laravel 12 dan PHP 8.2 atau lebih baru.

## Clone dan jalankan lokal

```sh
git clone https://github.com/hdzmxx/SimpelSKL.git
cd SimpelSKL
composer install
```

Salin `.env.example` menjadi `.env`, lalu isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` dengan kredensial lokal. Setelah itu:

```sh
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

Seeder membuat akun admin serta mapel bawaan, tetapi tidak memuat data siswa contoh. Database lokal menggunakan SQLite secara default. Untuk production, ikuti panduan berikut dan gunakan database production tersendiri.

## Deploy ke production

1. Siapkan server PHP sesuai `composer check-platform-reqs`, ekstensi Node.js/npm untuk membangun aset, database production, HTTPS, dan document root yang menunjuk ke direktori `public/`.
2. Pasang dependensi PHP dan aset:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   composer check-platform-reqs --no-dev
   npm ci
   npm run build
   ```

3. Buat `.env` di server (jangan masukkan ke Git). Atur minimal:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda
   APP_KEY=
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_database
   DB_USERNAME=nama_user
   DB_PASSWORD=password_database
   ADMIN_EMAIL=akun-admin
   ADMIN_PASSWORD=password-unik-minimal-12-karakter
   SEED_SAMPLE_DATA=false
   SESSION_SECURE_COOKIE=true
   ```

   Isi `APP_KEY` dengan `php artisan key:generate --show`, lalu simpan sebagai rahasia. Jangan mengganti key setelah aplikasi dipakai karena data terenkripsi dan sesi aktif dapat terdampak. Gunakan kredensial database terbatas untuk aplikasi dan pastikan database dicadangkan.

4. Jalankan migrasi dan seeder satu kali pada rilis pertama. Seeder membutuhkan `ADMIN_EMAIL` dan `ADMIN_PASSWORD`; seeder membuat akun admin serta mapel bawaan. Seeder menolak password admin di bawah 12 karakter pada production. Jangan menjalankan seeder berulang tanpa memahami bahwa kredensial admin akan disetel ulang dari `.env`.

   ```sh
   php artisan migrate --force
   php artisan db:seed --force
   ```

5. Siapkan izin tulis untuk `storage/` dan `bootstrap/cache/`, lalu optimalkan aplikasi:

   ```sh
   php artisan optimize
   ```

6. Setelah deploy, periksa `https://domain-anda/up`, log aplikasi, login admin, dan fungsi impor/ekspor. Simpan `public/uploads/` pada penyimpanan persisten dan cadangkan bersama database. Jika server memakai beberapa instance, bagikan direktori unggahan di antara instance.

### Data dan seeder

`database/seeders/data/siswa.json` adalah data fixture lokal, bukan data yang harus dipasang di production. Seeder tidak memuatnya secara default; untuk pengembangan non-production, aktifkan `SEED_SAMPLE_DATA=true`. Seeder akan menolak opsi tersebut di production. Jangan mengunggah atau meng-commit data siswa, database lokal, atau berkas `.env`.

## Pengembangan dan pemeriksaan

```sh
composer install
php artisan key:generate
php artisan migrate
composer test
npm ci
npm run build
```

Gunakan database lokal terpisah dari production. Sebelum merilis, pastikan tes, build aset, dan `composer check-platform-reqs --no-dev` berhasil.
