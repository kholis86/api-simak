# Analisis Performa API KRS Online (2000 Mahasiswa Simultan)

Dokumen ini berisi analisis teknis mengenai kemampuan API Simak dalam menangani beban kerja tinggi saat periode KRS Online, khususnya dengan estimasi 2000 mahasiswa yang mengakses sistem secara bersamaan.

## 1. Temuan Utama (Bottlenecks)

### 1.1 Ketergantungan pada Database (Sessions & Cache)

Berdasarkan konfigurasi di file `.env`, aplikasi saat ini menggunakan database untuk menyimpan session dan cache:

- `SESSION_DRIVER=database`
- `CACHE_STORE=database`

**Dampak:** Setiap request dari mahasiswa akan memicu minimal satu operasi pembacaan (Read) dan satu operasi penulisan (Write) ke tabel `sessions`. Dengan 2000 mahasiswa simultan, database akan menerima ribuan query tambahan per detik hanya untuk manajemen session, yang dapat menyebabkan antrean panjang (bottleneck) pada I/O database.

### 1.2 Limitasi Web Server

Konfigurasi `PHP_CLI_SERVER_WORKERS=4` di `.env` (meskipun untuk lokal) menunjukkan bahwa sistem ini memerlukan penanganan worker yang tepat di tingkat produksi.
**Dampak:** Jika web server (seperti Nginx + PHP-FPM) tidak diatur untuk menangani ribuan proses serentak, mahasiswa akan mengalami error "502 Bad Gateway" atau "504 Gateway Timeout" karena server kehabisan worker untuk memproses request.

### 1.3 Alur Logika KRS & Efisiensi Memori

Di `KrsController.php`, terdapat beberapa poin yang perlu diperhatikan:

- **Pengecekan Bertingkat:** Fungsi `postKrs` melakukan beberapa `SELECT` sebelum `INSERT`. Tanpa optimasi indexing atau caching, ini akan memperlambat waktu respon per mahasiswa.
- **Race Condition:** Penggunaan `DB::beginTransaction()` sudah baik, namun mekanisme penguncian (locking) untuk kuota belum diaktifkan. Jika nantinya ada batasan jumlah mahasiswa per kelas, sistem saat ini berisiko membiarkan pendaftar melebihi kapasitas (over-quota).
- **Memory Consumption:** Penggunaan `cursor()` untuk mengambil data KRS sudah sangat baik, namun pengelompokan (grouping) data dalam array besar sebelum dikembalikan tetap akan mengonsumsi RAM server yang signifikan.

---

## 2. Rekomendasi Optimasi

### 2.1 Infrastruktur & Konfigurasi

1.  **Migrasi ke Redis:** Sangat disarankan untuk memindahkan `SESSION_DRIVER` dan `CACHE_STORE` dari `database` ke `redis`. Redis berbasis RAM dan jauh lebih cepat dalam menangani session dalam skala besar.
2.  **Laravel Octane:** Pertimbangkan penggunaan Laravel Octane (dengan Swoole atau RoadRunner). Octane menjaga aplikasi tetap "hangat" di memori sehingga tidak perlu melakukan proses booting Laravel di setiap request, yang dapat meningkatkan throughput hingga berkali-kali lipat.
3.  **Database Connection Pooling:** Pastikan database (MySQL) dikonfigurasi untuk menangani jumlah koneksi yang cukup (`max_connections`) agar tidak menolak koneksi saat beban puncak.

### 2.2 Optimasi Kode (Backend)

1.  **Indexing Database:** Pastikan kolom yang sering digunakan dalam filter seperti `Student_Id`, `Term_Year_Id`, `Course_Id`, dan `Offered_Course_Id` memiliki index yang tepat.
2.  **Concurrency Control:** Jika ada pengecekan kuota, gunakan `sharedLock()` atau `lockForUpdate()` secara hati-hati di dalam transaksi untuk memastikan integritas data tanpa menyebabkan deadlock.
3.  **API Response Optimization:** Kurangi payload data yang dikembalikan ke mahasiswa. Berikan hanya data yang benar-benar dibutuhkan oleh frontend saat proses KRS berlangsung.

---

## 3. Kesimpulan

Secara default, API ini kemungkinan besar akan **tersendat atau bahkan lumpuh** jika dibebani 2000 mahasiswa simultan tanpa adanya optimasi pada sisi Session Storage (Redis) dan Web Server (worker tuning). Optimasi infrastruktur lebih krusial saat ini dibandingkan hanya mengubah logika kode.
