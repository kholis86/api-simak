# SIMAK API - Sistem Informasi Akademik

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

[![Laravel Version](https://img.shields.io/badge/Laravel-v12.0-red?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777bb4?style=flat-square&logo=php)](https://www.php.net)
[![RoadRunner](https://img.shields.io/badge/RoadRunner-Fast-blue?style=flat-square)](https://roadrunner.dev)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](https://opensource.org/licenses/MIT)

API Backend untuk Sistem Informasi Akademik (SIMAK) yang dibangun menggunakan Laravel 12 dengan performa tinggi menggunakan Laravel Octane dan RoadRunner.

---

## 🚀 Fitur Utama

- **High Performance**: Menggunakan Laravel Octane dengan RoadRunner.
- **RESTful API**: Dokumentasi lengkap menggunakan Swagger/L5-Swagger.
- **Authentication**: Mendukung Sanctum API Token dan Socialite (Google Login).
- **Academic Modules**: Pengelolaan KRS, KHS, AKM, Data Mahasiswa, dan Mata Kuliah.
- **Auto-Reload**: Pengembangan lebih cepat dengan watcher otomatis.

---

## 🛠 Prerequisites

Pastikan perangkat Anda sudah terinstall:

- **PHP 8.2** atau lebih tinggi
- **Composer** (Dependency Manager untuk PHP)
- **Node.js & NPM** (Untuk development tools)
- **SQLite/MySQL** (Default menggunakan SQLite)

---

## ⚙️ Instalasi Cepat

Ikuti langkah-langkah berikut untuk menjalankan project di lingkungan baru:

1. **Clone & Install Dependencies**

    ```bash
    composer install
    npm install
    ```

2. **Setup Environtment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Database & Seeding**

    ```bash
    # Buat file database jika menggunakan SQLite
    touch database/database.sqlite

    # Jalankan migrasi dan seeder
    php artisan migrate:fresh --seed
    ```

---

## 🏃 Menjalankan Aplikasi

Project ini dioptimalkan menggunakan **RoadRunner**. Anda bisa menjalankannya dengan beberapa cara:

### 1. Mode Development (dengan Auto-Reload)

Gunakan perintah ini agar server otomatis restart saat ada perubahan file:

```bash
./rr.exe serve -c .rr.dev.yaml
```

### 2. Mode Produksi

```bash
./rr.exe serve -c .rr.prod.yaml
```

### 3. Laravel Standard (Jika tidak ingin menggunakan RoadRunner)

```bash
php artisan serve
```

---

## 📖 Dokumentasi API

Aplikasi ini sudah dilengkapi dengan **Swagger UI**. Setelah server berjalan, Anda dapat mengakses dokumentasi API di:

🔗 **[http://localhost:8080/api/documentation](http://localhost:8080/api/documentation)**

---

## 🛠 Perintah Penting (Development)

| Perintah                          | Deskripsi                          |
| :-------------------------------- | :--------------------------------- |
| `php artisan octane:install`      | Install/Setup Octane               |
| `php artisan l5-swagger:generate` | Generate ulang dokumentasi Swagger |
| `php artisan test`                | Menjalankan unit & feature testing |
| `php artisan route:list`          | Melihat semua daftar endpoint      |

---

## 📁 Struktur Penting

- `app/Http/Controllers`: Logika bisnis API.
- `routes/api.php`: Definisi endpoint API.
- `.rr.yaml`: Konfigurasi server RoadRunner.
- `docs/`: File dokumentasi tambahan dan walkthrough.

---

## 📄 Lisensi

Project ini dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).
