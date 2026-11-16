# Borang Audit

Borang dalaman (alternatif Google Form) untuk Unit Audit Dalam KEJORA. Dibina dengan **Laravel 13 + Tailwind CSS 4 + Alpine.js**.

## Ciri utama
- **Pembina borang** (admin): tajuk, penerangan, status (Draf/Dibuka/Ditutup), tarikh tutup, susun soalan.
- Jenis soalan: jawapan pendek/panjang, pilihan tunggal/berbilang, juntai bawah, bintang, **Pilih imej/reka bentuk** (dengan galeri zoom + komen), **Maklum balas setiap bahagian** (Suka / Boleh diterima / Perlu penambahbaikan + komen), tajuk bahagian.
- Pautan awam `/f/{slug}`: responsif (telefon/desktop), pengesahan, had satu hantaran setiap peranti (boleh dimatikan).
- **Keputusan**: ringkasan bar/peratus, komen dikumpul mengikut pilihan/bahagian, maklum balas individu, **eksport CSV** (Excel).
- Borang contoh siap: *Pemilihan Draf Konsep Buku Laporan Tahunan UAD 2025* (imej draf dalam `public/images/uad`).

## Jalankan secara tempatan (Laragon)
```bash
composer install
copy .env.example .env
php artisan key:generate
# edit ADMIN_EMAIL / ADMIN_PASSWORD dalam .env
php artisan migrate --seed
npm install && npm run build   # public/build sudah disertakan, langkah ini pilihan
```
Buka `http://auditform.test` (Laragon auto-virtual-host) → `/login`.

Pembangunan UI: `npm run dev`.

## Deploy ke FlashPanel (site sementara)
1. **New Site** → tanda *Use Free Domain*, isi nama domain, pilih **php8.3**.
2. **Root Document** mesti menunjuk ke folder `public`, cth `/home/flashpanel/<domain>/public`.
3. Muat naik projek (Git atau SFTP) ke `/home/flashpanel/<domain>` — `vendor` dan `node_modules` tidak perlu dimuat naik.
4. Dalam terminal tapak:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   touch database/database.sqlite
   ```
5. Edit `.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<domain-sementara-anda>
   ADMIN_EMAIL=emel-anda@kejora.gov.my
   ADMIN_PASSWORD=kata-laluan-kukuh
   ```
6. `php artisan migrate --seed --force` kemudian `php artisan config:cache route:cache view:cache`.
7. Pastikan folder `storage`, `bootstrap/cache`, `database` dan `public/uploads` boleh ditulis oleh pengguna web (`chmod -R ug+rwX`).
8. Log masuk di `https://<domain>/login`, buka borang → tukar status ke **Dibuka** → **Salin pautan** dan kongsi.

Aset (`public/build`) sudah dibina, jadi **Node tidak diperlukan** di pelayan.
Jika SQLite tidak tersedia, guna MySQL: tetapkan `DB_CONNECTION=mysql` dan butiran `DB_*` dalam `.env`.

> Domain percuma FlashPanel dipadam selepas 14 hari. Eksport CSV dan salin `database/database.sqlite` sebelum tamat.

## Keselamatan
- Tukar `ADMIN_PASSWORD` sebelum deploy; jangan commit `.env`.
- Halaman admin memerlukan log masuk; borang awam `noindex`.
- Muat naik imej hanya untuk admin (JPG/PNG/WEBP, maks 8MB).

## Ujian
```bash
php artisan test
```
