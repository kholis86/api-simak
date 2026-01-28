# Panduan Deployment Production (DigitalOcean)

## Laravel Octane + RoadRunner

Berikut adalah langkah-alih (step-by-step) untuk meng-upload dan menjalankan proyek ini di server DigitalOcean (asumsi menggunakan Ubuntu).

### 1. Persiapan Server

Pastikan server sudah terinstal:

- PHP 8.2+ dengan ekstensi yang diperlukan (GD, MySQL, BCMath, Zip).
- Composer.
- Nginx (sebagai Reverse Proxy).

### 2. Upload & Install Proyek

```bash
# Clone proyek
git clone [URL_REPO] api-simak
cd api-simak

# Install dependensi (Mode Produksi)
composer install --no-dev --optimize-autoloader

# Salin & Sesuaikan .env
cp .env.example .env
nano .env
# Set: APP_ENV=production, APP_DEBUG=false, CACHE_STORE=file, OCTANE_SERVER=roadrunner
```

### 3. Install RoadRunner Binary (Linux)

Karena di lokal menggunakan Windows (`rr.exe`), di server Anda harus mengunduh binary versi Linux:

```bash
php artisan octane:install --server=roadrunner
# Pilih 'yes' untuk mendownload binary Linux
```

### 4. Konfigurasi Systemd (Auto-Run)

Agar RoadRunner berjalan otomatis di background dan restart jika server mati.
Buat file service baru:

```bash
sudo nano /etc/systemd/system/octane.service
```

Isi dengan konfigurasi berikut (sesuaikan path `/var/www/api-simak`):

```ini
[Unit]
Description=Laravel Octane RoadRunner
After=network.target

[Service]
User=www-data
Group=www-data
Type=simple
WorkingDirectory=/var/www/api-simak
ExecStart=/usr/bin/php /var/www/api-simak/artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8080 --workers=4
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Jalankan service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable octane
sudo systemctl start octane
```

### 5. Konfigurasi Nginx (Reverse Proxy)

Nginx akan menerima traffic dari port 80/443 dan mengoperkannya ke Octane (port 8080).

```bash
sudo nano /etc/nginx/sites-available/api-simak
```

Isi konfigurasi (cuplikan utama):

```nginx
server {
    listen 80;
    server_name api.domainanda.com;
    root /var/www/api-simak/public;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### 6. Optimalisasi Terakhir

Jalankan perintah ini di production:

```bash
php artisan config:cache
php artisan route:cache
```

> [!IMPORTANT]
> Jangan lupa jalankan `php artisan key:generate` dan `php artisan migrate` jika database bersifat baru.
