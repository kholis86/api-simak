# Walkthrough: Laravel Octane + RoadRunner Implementation

Saya telah berhasil mengimplementasikan **Laravel Octane** dengan server **RoadRunner** di lingkungan Windows. Sesuai permintaan, sistem dikonfigurasi menggunakan **cache lokal (file)** dan bukan Redis.

## Perubahan yang Dilakukan

### 1. Instalasi & Konfigurasi

- **Package:** Menginstal `laravel/octane` beserta dependensi `spiral/roadrunner`.
- **Binary:** Mengunduh `rr.exe` (RoadRunner binary) versi 2025.1.6.
- **Environment:**
    - `OCTANE_SERVER=roadrunner`
    - `CACHE_STORE=file` (Lokal)
- **Files:**
    - Membuat [app.php](app.php) sebagai wrapper worker agar bisa berjalan di Windows dengan path yang benar.
    - Menyesuaikan [.rr.yaml](.rr.yaml) untuk menggunakan wrapper tersebut.

### 2. Perbaikan Windows-Specific (Fixes)

- **Signal Constants:** Saya memodifikasi file [artisan](artisan) untuk mendefinisikan konstanta POSIX (`SIGINT`, `SIGTERM`, `SIGHUP`) yang biasanya tidak ada di PHP Windows. Ini mengatasi error saat Octane mencoba melakukan manajemen worker.

### 3. API Offered Course

- **SKS Calculation:** Menjumlahkan kolom `Sks_Tm`, `Sks_Prak`, `Sks_Prak_Lap`, dan `Sks_Sim` dari tabel `acd_course`.
- **Capacity Calculation:** Menghitung sisa kuota dengan rumus `Class_Capacity` dikurangi jumlah student di `acd_student_krs`.

### 4. API Students

- **Total SKS Calculation:** Menambahkan field `Total_Sks` yang menjumlahkan SKS dari mata kuliah unik (mengatasi course_id ganda) untuk tiap mahasiswa. Value ini juga dipetakan ke field `SKS Diakui` pada versi detail.
- **IPK Calculation:** Menambahkan field `Ipk` dengan rumus `SUM(Bnk_Value) / NULLIF(SUM(Sks), 0)` dari tabel `acd_transcript`. Query menggunakan subquery untuk mengambil nilai terbaik (`MAX(Bnk_Value)` & `MAX(Sks)`) jika ada mata kuliah yang diulang.
- **KRS Period Validation:** Menambahkan validasi pada `postKrs` dan `deleteKrs` untuk memastikan transaksi hanya bisa dilakukan jika tanggal saat ini berada dalam rentang `Start_Date` dan `End_Date` dari `mstr_term_year`.
- **DPA (Dosen Pembimbing Akademik):** Menambahkan field `Dpa` pada data mahasiswa yang mengambil nama dosen dari tabel `acd_student_supervision` dan `emp_employee`.
- **Avatar Logic:** Menambahkan field `Avatar` yang mengembalikan URL foto mahasiswa. Jika path dimulai dengan `http`, digunakan langsung. Jika tidak, dianggap file lokal di `storage/`. Jika tidak ada foto, menggunakan layanan `ui-avatars.com`.

## Hasil Verifikasi

### Pengujian Beban (Load Test)

Saya menjalankan pengujian menggunakan **k6** pada endpoint `POST /api/post-krs` di port **8080** (Octane Server).

**Hasil:**

- **Total Iterasi:** 2954 request berhasil diproses.
- **Durasi:** ~35 detik.
- **Throughput:** ~83 request per detik (RPS).
- **Status:** Berhasil (Status 201/422 sesuai logika bisnis).

## Cara Menjalankan Selanjutnya

Jika server terhenti, Anda bisa menjalankannya kembali dengan perintah:

```powershell
** run dev**
** terminal 1
./rr.exe serve -c .rr.dev.yaml
** terminal 2
npx chokidar-cli "app/**/*.php" "routes/**/*.php" "config/**/*.php" -c "rr.exe reset"

**run prod**
./rr.exe serve -c .rr.prod.yaml
```

> [!NOTE]
> Penggunaan Octane sangat meningkatkan _throughput_ karena aplikasi Laravel tetap berada di memori (booting hanya sekali), bukan melakukan proses booting di setiap request.
