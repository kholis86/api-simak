# Checklist Persiapan KRS Online (Beban 2000 Mahasiswa)

Gunakan checklist ini sebagai panduan teknis bagi tim pengembang dan tim IT (Ops) sebelum periode KRS dibuka.

## 1. Konfigurasi Aplikasi (Laravel)

Bagian ini diatur di file `.env` dan kode program.

- [ ] **Ganti Session Driver ke Redis:** Ubah `SESSION_DRIVER=database` menjadi `SESSION_DRIVER=redis`. Ini krusial untuk mengurangi beban I/O database.
- [ ] **Ganti Cache Driver ke Redis:** Ubah `CACHE_STORE=database` menjadi `CACHE_STORE=redis`.
- [ ] **Matikan APP_DEBUG:** Pastikan `APP_DEBUG=false` di lingkungan produksi untuk keamanan dan performa.
- [ ] **Optimalisasi Class & Route:** Jalankan perintah berikut di server produksi:
    - `php artisan config:cache`
    - `php artisan route:cache`
    - `php artisan view:cache`
- [ ] **Implementasi Eager Loading:** Pastikan tidak ada query `N+1` di controller (terutama saat menampilkan daftar mata kuliah).

## 2. Optimasi Database (MySQL)

Mencegah database menjadi lambat saat ribuan mahasiswa mencari data.

- [ ] **Database Indexing:** Pastikan kolom berikut memiliki Index:
    - Tabel `acd_student_krs`: `Student_Id`, `Term_Year_Id`, `Course_Id`.
    - Tabel `acd_offered_course`: `Term_Year_Id`, `Department_Id`.
- [ ] **Max Connections:** Naikkan limit koneksi database (minimal set ke `1000` atau lebih tergantung kapasitas RAM server).
- [ ] **InnoDB Buffer Pool:** Atur `innodb_buffer_pool_size` minimal 50-70% dari total RAM server agar data tersimpan di memori.
- [ ] **Monitoring Slow Query:** Aktifkan _slow query log_ untuk memantau jika ada proses pendaftaran yang memakan waktu lebih dari 1 detik.

## 3. Konfigurasi Server (Web Server & PHP)

Menyiapkan "pintu masuk" agar tidak antre.

- [ ] **PHP-FPM Tuning:**
    - Atur `pm = static` atau `dynamic` dengan `pm.max_children` yang cukup besar (sesuaikan dengan RAM, asumsi 1 child = 30-50MB).
    - Pastikan `pm.max_requests` diatur (misal 500) untuk mencegah memory leak.
- [ ] **Nginx Tuning:**
    - Naikkan `worker_connections` ke `1024` atau lebih.
    - Atur `keepalive_timeout` yang efisien.
- [ ] **Opcache:** Pastikan PHP Opcache aktif (`opcache.enable=1`) untuk mempercepat eksekusi skrip PHP.
- [ ] **Laravel Octane (Opsional tapi Sangat Disarankan):** Jika memungkinkan, gunakan Swoole atau RoadRunner untuk meningkatkan throughput hingga 5x lipat.

## 4. Pengujian Beban (Load Testing)

Jangan menunggu hari-H untuk mengetahui sistem tidak kuat.

- [ ] **Simulasi Jmeter/Locust:** Lakukan uji beban dengan mensimulasikan 500, 1000, lalu 2000 user secara bertahap.
- [ ] **Check Error Rate:** Pastikan tingkat error (500/502/504) di bawah 1%.
