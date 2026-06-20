# Dokumentasi UKRIDA LabReserve

Aplikasi web untuk **reservasi/peminjaman Laboratorium Komputer UKRIDA**. Dosen dan tim mahasiswa dapat mengajukan reservasi melalui kalender mingguan, sementara admin meninjau, menyetujui, dan mengelola seluruh data lab (komputer, pengguna, laporan, audit log, serta pengaturan operasional).

> Dokumen ini berisi: **petunjuk instalasi**, **petunjuk penggunaan**, dan **rincian setiap fitur/menu** aplikasi.

---

## Daftar Isi

1. [Tentang Aplikasi](#1-tentang-aplikasi)
2. [Teknologi yang Digunakan](#2-teknologi-yang-digunakan)
3. [Persyaratan Sistem](#3-persyaratan-sistem)
4. [Instalasi (Langkah demi Langkah)](#4-instalasi-langkah-demi-langkah)
5. [Konfigurasi `.env`](#5-konfigurasi-env)
6. [Menyiapkan Database](#6-menyiapkan-database)
7. [Menjalankan Aplikasi](#7-menjalankan-aplikasi)
8. [Akun Default](#8-akun-default)
9. [Peran Pengguna (Role)](#9-peran-pengguna-role)
10. [Alur Login](#10-alur-login)
11. [Panduan Penggunaan untuk Dosen / Tim](#11-panduan-penggunaan-untuk-dosen--tim)
12. [Rincian Fitur Admin](#12-rincian-fitur-admin)
13. [Struktur Database](#13-struktur-database)
14. [Aturan Bisnis Lab](#14-aturan-bisnis-lab)
15. [Perintah Artisan yang Berguna](#15-perintah-artisan-yang-berguna)
16. [Pemecahan Masalah (Troubleshooting)](#16-pemecahan-masalah-troubleshooting)
17. [Lampiran A — Menjalankan dengan Docker](#17-lampiran-a--menjalankan-dengan-docker)

---

## 1. Tentang Aplikasi

UKRIDA LabReserve adalah sistem reservasi laboratorium berbasis web. Inti alurnya:

- **Pengguna (dosen / tim)** membuka **Kalender Mingguan**, memilih slot kosong, lalu mengajukan reservasi langsung dari popover (satu langkah).
- Reservasi masuk dengan status **`submitted`** dan menunggu peninjauan admin.
- **Admin** meninjau permintaan, lalu **menyetujui (approve)**, **menolak (reject)**, atau menandai **selesai (complete)**.
- Pengguna mengisi **Logbook** (catatan kegiatan) untuk reservasi yang sudah disetujui/selesai.
- Semua perubahan penting tercatat di **Audit Log**, dan admin dapat melihat **Laporan analitik** beserta ekspor **PDF**.

Tidak ada pendaftaran publik — **semua akun dibuat oleh admin**.

---

## 2. Teknologi yang Digunakan

| Komponen        | Teknologi                                              |
|-----------------|--------------------------------------------------------|
| Framework       | Laravel 12 (PHP 8.2+)                                   |
| Database        | MySQL / MariaDB (via XAMPP)                             |
| Frontend        | Blade + Alpine.js 3 + Tailwind CSS v3                   |
| Build Tool      | Vite 7                                                  |
| Auth/Scaffolding| Laravel Breeze (disesuaikan untuk login multi-langkah) |
| Font            | Sora & JetBrains Mono                                   |

---

## 3. Persyaratan Sistem

Pastikan perangkat telah terpasang:

- **XAMPP** (menyertakan Apache, PHP 8.2+, dan MySQL/MariaDB) — atau PHP 8.2+ & MySQL berdiri sendiri.
- **Composer** (manajer paket PHP).
- **Node.js 18+** dan **npm** (untuk membangun aset frontend).
- **Git** (opsional, untuk mengunduh dari GitHub).

Periksa versi:

```bash
php -v          # >= 8.2
composer -V
node -v         # >= 18
npm -v
```

---

## 4. Instalasi (Langkah demi Langkah)

Contoh berikut mengasumsikan proyek berada di `c:\xampp\htdocs\UKRIDA_LabReserve` (instalasi XAMPP standar di Windows).

### 4.1. Dapatkan kode sumber

```bash
# Jika dari GitHub
cd c:\xampp\htdocs
git clone <url-repo> UKRIDA_LabReserve
cd UKRIDA_LabReserve
```

Atau cukup salin/ekstrak folder proyek ke dalam `c:\xampp\htdocs\`.

### 4.2. Pasang dependensi PHP

```bash
composer install
```

### 4.3. Pasang dependensi JavaScript

```bash
npm install
```

### 4.4. Siapkan file `.env`

```bash
copy .env.example .env
```

Lalu sesuaikan isinya (lihat [bagian 5](#5-konfigurasi-env)).

### 4.5. Buat application key

```bash
php artisan key:generate
```

### 4.6. Bangun aset frontend

> **Penting:** proyek ini **tidak menjalankan Vite dev server** secara default. Aplikasi menyajikan aset hasil build di `public/build`. Setiap kali ada perubahan pada kelas Tailwind / CSS / JS, jalankan ulang build agar perubahan tampak.

```bash
npm run build
```

---

## 5. Konfigurasi `.env`

File `.env.example` bawaan menggunakan SQLite. Untuk lingkungan XAMPP, ubah ke MySQL. Bagian penting:

```env
APP_NAME="UKRIDA LabReserve"
APP_ENV=local
APP_KEY=                      # otomatis terisi oleh "php artisan key:generate"
APP_DEBUG=true
APP_URL=http://localhost

# --- Database (MySQL via XAMPP) ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=UKRIDA_LabReserve
DB_USERNAME=root
DB_PASSWORD=                  # default XAMPP: kosong

# Session & antrean memakai database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Email (default "log": email hanya dicatat ke storage/logs, tidak dikirim)
MAIL_MAILER=log
```

> Pastikan baris `DB_CONNECTION=mysql` (bukan `sqlite`) dan empat baris `DB_*` di bawahnya **tidak** dikomentari.

---

## 6. Menyiapkan Database

Ada **dua cara**. Pilih salah satu.

### Cara A — Migrasi + Seeder (disarankan untuk instalasi baru)

1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) dan buat database kosong bernama `UKRIDA_LabReserve`.
2. Jalankan migrasi untuk membuat seluruh tabel:

   ```bash
   php artisan migrate
   ```

3. Isi data awal (program studi, akun admin, 9 komputer, pengaturan lab):

   ```bash
   php artisan db:seed
   ```

4. (Opsional) Tambahkan akun dosen & tim uji coba:

   ```bash
   php artisan db:seed --class=TestLecturerSeeder
   ```

### Cara B — Impor dump SQL

Tersedia dump siap pakai di `Plan/ukrida_labreserve.sql` (sudah berisi data contoh). Buat database `UKRIDA_LabReserve` lalu impor file tersebut melalui phpMyAdmin, atau:

```bash
mysql -u root UKRIDA_LabReserve < Plan/ukrida_labreserve.sql
```

> Setelah impor, jika nanti ada migrasi baru, jalankan `php artisan migrate` — tabel `migrations` di dump sudah konsisten sehingga tidak akan menjalankan ulang migrasi yang sudah ada.

---

## 7. Menjalankan Aplikasi

### Opsi 1 — via XAMPP (Apache)

Karena proyek berada di `htdocs`, akses melalui:

```
http://localhost/UKRIDA_LabReserve/public
```

### Opsi 2 — via server bawaan Laravel (disarankan saat pengembangan)

```bash
php artisan serve
```

Lalu buka `http://127.0.0.1:8000`.

Halaman utama (`/`) otomatis mengarahkan ke halaman **login**.

---

## 8. Akun Default

Setelah `php artisan db:seed`:

### Admin (login langsung via email + kata sandi)

| Field         | Nilai                  |
|---------------|------------------------|
| Halaman login | `/admin/login`         |
| Email         | `admin@ukrida.ac.id`   |
| Kata sandi    | `Admin@123`            |

### Program Studi (gerbang login dosen/tim — Langkah 1)

Login dosen/tim memakai **Gmail program studi + kata sandi bersama**. Default seeder:

| Program Studi        | Gmail (Langkah 1)        | Kata sandi |
|----------------------|--------------------------|------------|
| Teknik Informatika   | `ti.ukrida@gmail.com`    | `Test@123` |
| Sistem Informasi     | `si.ukrida@gmail.com`    | `Test@123` |
| Teknik Elektro       | `te.ukrida@gmail.com`    | `Test@123` |
| Teknik Industri      | `tk.ukrida@gmail.com`    | `Test@123` |

### Akun uji coba (hanya jika `TestLecturerSeeder` dijalankan)

| Nama              | Peran    | Program Studi       |
|-------------------|----------|---------------------|
| Dr. Budi Santoso  | lecturer | Teknik Informatika  |
| Tim Alpha         | team     | Teknik Informatika  |

> Demi keamanan, **ganti semua kata sandi default** sebelum digunakan di lingkungan nyata.

---

## 9. Peran Pengguna (Role)

| Peran      | Keterangan                                                                 |
|------------|----------------------------------------------------------------------------|
| `admin`    | Mengelola seluruh sistem. Tidak terikat program studi. Login via `/admin/login`. |
| `lecturer` | Dosen. Membuat & mengelola reservasi miliknya, mengisi logbook.            |
| `team`     | Akun tim/kelompok mahasiswa (memiliki PIC dosen). Hak setara dosen untuk reservasi. |

Hanya akun dengan `is_active = true` yang dapat masuk (middleware `active`). Akses menu admin dilindungi middleware `admin`.

---

## 10. Alur Login

### Login Dosen / Tim — dua langkah

1. **Langkah 1 — Gerbang Program Studi** (`/login`): pengguna memasukkan **Gmail program studi** + **kata sandi program studi**. Sistem mendeteksi program studi yang cocok. (Dibatasi: maksimal 5 percobaan gagal per email+IP.)
2. **Langkah 2 — Pilih Pengguna** (`/login/select`): muncul daftar dosen & tim aktif pada program studi tersebut. Pengguna memilih namanya, lalu masuk. Setelah berhasil, diarahkan ke **Dashboard**.

### Login Admin — langsung

Admin masuk di `/admin/login` menggunakan **email + kata sandi** secara langsung, lalu diarahkan ke **Dashboard Admin**.

---

## 11. Panduan Penggunaan untuk Dosen / Tim

### 11.1. Dashboard

Halaman ringkasan setelah login: reservasi terkini dan akses cepat ke kalender.

### 11.2. Membuat Reservasi (Kalender Mingguan)

1. Buka menu **Kalender**. Tampil jadwal mingguan (1 minggu lampau + minggu ini + 3 minggu ke depan).
2. Klik slot waktu kosong untuk membuka **popover reservasi**.
3. Isi form:
   - **Tipe reservasi:**
     - **Komputer saja** (`computers_only`) — memilih **1 unit** komputer.
     - **Ruang + Komputer** (`full_room`) — memakai seluruh lab; unit komputer ditautkan untuk tampilan.
     - **Ruang saja** (`room_only`) — pilih mode **Eksklusif** atau **Berbagi (shared)**.
   - **Tanggal**, **waktu mulai**, **waktu selesai** (tidak boleh di masa lalu; selesai harus setelah mulai).
   - **Alasan/tujuan** reservasi (min. 3 karakter) — ini menjadi catatan awal logbook.
   - **Kategori:** penelitian, tugas akhir, project akademik, praktikum, atau lainnya.
   - Opsi tambahan: butuh internet, butuh instalasi software, perangkat eksternal.
4. Kirim. Reservasi mendapat **kode** unik dan berstatus **`submitted`** (menunggu admin).

Sistem otomatis menolak slot yang bentrok dengan reservasi yang sudah **disetujui**, serta memvalidasi jam/hari operasional dan durasi maksimum (lihat [Aturan Bisnis Lab](#14-aturan-bisnis-lab)).

### 11.3. Status Reservasi

| Status         | Arti                                                        |
|----------------|-------------------------------------------------------------|
| `submitted`    | Terkirim, menunggu ditinjau admin.                          |
| `under_review` | Sedang ditinjau (otomatis saat admin membuka detailnya).    |
| `approved`     | Disetujui.                                                  |
| `rejected`     | Ditolak (admin menyertakan alasan).                         |
| `cancelled`    | Dibatalkan oleh pengguna.                                   |
| `completed`    | Selesai (ditandai admin setelah sesi berakhir).             |
| `draft`        | Status awal internal sebelum dikirim.                       |

### 11.4. Detail & Pembatalan Reservasi

Dari **Riwayat** atau kalender, buka detail sebuah reservasi untuk melihat informasi lengkap. Reservasi dapat **dibatalkan** selama statusnya `submitted`, `under_review`, atau `approved`.

### 11.5. Logbook

**Logbook** adalah catatan kegiatan per-reservasi. Dapat diisi untuk reservasi berstatus `approved` atau `completed`, melalui halaman detail reservasi maupun menu **Logbook**.

Field logbook:

- **Checkpoint / Progress Kegiatan** *(wajib, min. 10 karakter)* — apa yang berhasil dikerjakan.
- **Nama Pembimbing** *(opsional)*.
- **Mata Kuliah Terkait** *(opsional)*.
- Field lain (kategori, kebutuhan internet/instalasi, software khusus, perangkat eksternal) terbawa dari saat pengajuan.

> Khusus **software khusus** (`special_software`) yang diisi saat butuh instalasi akan muncul di **Laporan → Instalasi Software** milik admin.

### 11.6. Riwayat

Menu **Riwayat** menampilkan seluruh reservasi milik pengguna beserta statusnya.

---

## 12. Rincian Fitur Admin

Semua menu di bawah berada di bawah prefiks `/admin` dan hanya dapat diakses oleh akun `admin`.

### 12.1. Dashboard Admin (`/admin/dashboard`)

Ringkasan operasional lab: jumlah permintaan menunggu, status komputer, dan metrik utama lainnya.

### 12.2. Permintaan Reservasi (`/admin/requests`)

Pusat peninjauan reservasi.

- **Daftar & filter:** berdasarkan status (tab *pending* mencakup `submitted` + `under_review`), tanggal, dan pencarian (kode reservasi / nama pengguna).
- **Detail (`/admin/requests/{booking}`):** membuka detail otomatis mengubah status `submitted` → `under_review`, dan menampilkan **peringatan bentrok langsung** terhadap reservasi yang sudah disetujui.
- **Setujui (Approve):** memeriksa ulang konflik dalam transaksi terkunci. Jika ada bentrok, persetujuan dibatalkan. Saat berhasil, reservasi lain yang menunggu dan bentrok akan **otomatis ditolak**. Reservasi bertanggal lampau memerlukan konfirmasi tambahan.
- **Tolak (Reject):** wajib menyertakan **catatan/alasan** untuk pengguna.
- **Tandai Selesai (Complete):** hanya untuk reservasi `approved`, mengubah status menjadi `completed`.

### 12.3. Komputer (`/admin/computers`)

Mengelola 9 unit komputer lab.

- Setiap unit punya **nomor unit**, **label** (mis. `PC-01`), **status**, dan **catatan spesifikasi**.
- Status komputer: **`online`** (tersedia untuk dipesan), **`maintenance`**, atau **`offline`**.
- Perubahan status komputer dicatat di **Audit Log** (`computer.status_changed`, menyimpan status lama → baru). Unit non-`online` tidak dapat dipilih saat reservasi.

### 12.4. Pengguna (`/admin/users`)

Mengelola akun dosen dan tim.

- **Daftar**, **buat**, dan **edit** pengguna.
- Setiap pengguna terhubung ke **program studi**, memiliki **peran** (`lecturer`/`team`) dan status **aktif/nonaktif**.
- Akun nonaktif tidak dapat masuk.

### 12.5. Tim (`/admin/teams`)

Mengelola **akun tim** (kelompok mahasiswa).

- **Buat** dan **edit** tim, lengkap dengan **PIC dosen** (penanggung jawab) dan program studi.
- Setiap tim memiliki akun pengguna tersendiri (peran `team`).

### 12.6. Laporan (`/admin/reports`)

Halaman analitik dengan filter periode dan ekspor PDF.

- **Filter periode:** preset **Minggu / Bulan / Kuartal / Tahun**, atau **rentang tanggal kustom**. Setiap preset mencakup rentang penuh periodenya (mis. "Bulan" sampai akhir bulan) sehingga reservasi mendatang yang sudah disetujui tetap terhitung.
- **Isi laporan:**
  - **Pita KPI** (ringkasan angka utama).
  - **Grafik mingguan** (bar) dan **donut** distribusi status/tipe.
  - **Tabel pengguna teratas** dengan avatar inisial.
  - **Pemakaian per komputer** (bar per PC).
  - **Instalasi Software** — menampilkan **software apa** yang diinstal dan **pada PC mana**, diambil dari logbook reservasi `approved`/`completed` yang menandai *butuh instalasi* dan mengisi *software khusus*.
- **Ekspor PDF:** tombol **"Cetak PDF"** menggunakan fitur **cetak browser** (`window.print()`). Pilihan ini sengaja dipakai agar aplikasi tetap ringan (tanpa pustaka PDF tambahan). Elemen navigasi disembunyikan otomatis saat mencetak. Pada dialog cetak, pilih **"Save as PDF"**.

### 12.7. Audit Log (`/admin/audit-log`)

Jejak audit seluruh aktivitas penting, ditampilkan sebagai **lini masa yang dikelompokkan per hari**.

- **Filter aksi berbasis checkbox:** pilih jenis aktivitas yang ingin ditampilkan (mis. logbook diperbarui, reservasi disetujui/ditolak, pengaturan diubah, status komputer berubah). Secara **default semua tercentang**; mengosongkan semua centang akan menyembunyikan seluruh entri.
- **Kartu statistik (mengikuti filter):**
  - **Total aktivitas** — mencerminkan **total hasil yang difilter**.
  - **Hari ini** — total hari ini **dalam cakupan filter** yang dipilih.
  - Kartu lain (mis. diproses, pengguna aktif) bersifat global.
- Setiap entri menampilkan waktu, pelaku, jenis aksi (dengan warna aksen), target, dan **selisih perubahan (diff)** yang dapat diperluas.
- **Ekspor PDF:** sama seperti Laporan, tombol **"Cetak PDF"** memakai cetak browser; diff dipaksa tampil penuh dan elemen navigasi disembunyikan saat dicetak.

### 12.8. Pengaturan Lab (`/admin/settings`)

Mengatur parameter operasional lab. Setiap perubahan dicatat di Audit Log (`settings.updated`).

| Pengaturan          | Keterangan                                            | Default                  |
|---------------------|-------------------------------------------------------|--------------------------|
| `lab_name`          | Nama laboratorium                                     | Laboratorium Komputer UKRIDA |
| `admin_email`       | Email admin penerima notifikasi                       | admin@ukrida.ac.id       |
| `operating_start`   | Jam buka                                              | 08:00                    |
| `operating_end`     | Jam tutup (harus setelah jam buka)                    | 22:00                    |
| `operating_days`    | Hari operasional (ISO: 1=Senin … 7=Minggu)            | 1–6 (Senin–Sabtu)        |
| `max_session_hours` | Durasi maksimum satu sesi (1–8 jam)                   | 4                        |
| `buffer_minutes`    | Jeda antar sesi (0–60 menit)                          | 15                       |

---

## 13. Struktur Database

Tabel utama:

| Tabel               | Fungsi                                                                                  |
|---------------------|-----------------------------------------------------------------------------------------|
| `study_programs`    | Program studi. Berisi `name`, `email` (Gmail gerbang login), `password` bersama, `is_active`. |
| `users`             | Akun pengguna. `role` (`admin`/`lecturer`/`team`), `study_program_id`, `is_active`, `last_login_at`. |
| `teams`             | Akun tim/kelompok. Menyimpan `pic_lecturer_id`, `study_program_id`.                     |
| `team_members`      | Anggota tiap tim.                                                                        |
| `computers`         | Unit komputer. `unit_number`, `label`, `status` (`online`/`maintenance`/`offline`), `specs_note`. |
| `bookings`          | Reservasi. `booking_code`, `booking_type`, `room_sharing`, `date`, jam, `status`, `admin_notes`, `reviewed_by`. |
| `booking_computers` | Tabel pivot reservasi ↔ komputer.                                                       |
| `booking_logbooks`  | Logbook per reservasi (relasi 1:1). Berisi `category`, `checkpoint_progress`, `special_software`, dll. |
| `audit_logs`        | Jejak audit seluruh aksi penting.                                                       |
| `lab_settings`      | Pengaturan lab (pasangan `key`/`value`).                                                 |

**Relasi penting:**
- `User` → `StudyProgram` (belongsTo), `User` → `Booking` (hasMany).
- `Booking` → `Computer` (belongsToMany via `booking_computers`), `Booking` → `BookingLogbook` (hasOne).
- `Team` → `User` (akun tim) & `User` (PIC dosen).

> Catatan: kolom `priority_reason` dan `session_target` pada `booking_logbooks` telah **dihapus** (migrasi `2026_06_20_000001`).

---

## 14. Aturan Bisnis Lab

Saat membuat reservasi, sistem memvalidasi (berdasarkan `lab_settings`):

- **Hari operasional** — tanggal harus termasuk hari yang diizinkan (`operating_days`).
- **Jam operasional** — `start_time` ≥ jam buka, `end_time` ≤ jam tutup.
- **Durasi maksimum** — durasi tidak melebihi `max_session_hours`.
- **Status komputer** — hanya unit `online` yang dapat dipilih.
- **Tidak boleh masa lalu** — tanggal harus hari ini atau setelahnya.
- **Deteksi bentrok** — slot yang sudah dikunci reservasi `approved` tidak dapat dipakai. Beberapa permintaan *pending* untuk slot sama diperbolehkan; admin yang menentukan pemenangnya saat approve (yang lain otomatis ditolak).

---

## 15. Perintah Artisan yang Berguna

```bash
php artisan migrate              # Jalankan migrasi tabel
php artisan migrate --force      # Jalankan migrasi di lingkungan produksi
php artisan migrate:fresh --seed # Reset total + isi ulang data awal (HATI-HATI: menghapus data)
php artisan db:seed              # Isi data awal
php artisan view:clear           # Bersihkan cache view Blade
php artisan config:clear         # Bersihkan cache konfigurasi
php artisan serve                # Jalankan server pengembangan
```

Build aset frontend:

```bash
npm run build                    # Build produksi (wajib setelah ubah Tailwind/CSS/JS)
npm run dev                      # (opsional) Vite dev server
```

---

## 16. Pemecahan Masalah (Troubleshooting)

| Masalah                                              | Penyebab & Solusi                                                                                          |
|------------------------------------------------------|------------------------------------------------------------------------------------------------------------|
| Tampilan berantakan / kelas Tailwind baru tidak muncul | Aset belum dibangun ulang. Jalankan **`npm run build`**. Proyek menyajikan aset dari `public/build`, bukan dev server. |
| Halaman lama masih muncul setelah edit Blade         | Jalankan **`php artisan view:clear`**.                                                                      |
| Error `SQLSTATE` / tidak bisa konek database         | Periksa `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Pastikan MySQL XAMPP berjalan dan database sudah dibuat. |
| `No application encryption key has been specified`   | Jalankan **`php artisan key:generate`**.                                                                    |
| Tidak bisa login dosen/tim                           | Pastikan program studi **aktif**, kata sandi program benar, dan ada **pengguna aktif non-admin** pada program tersebut. |
| Kena rate limit saat login                           | Maksimal 5 percobaan gagal per email+IP. Tunggu sesuai pesan, atau bersihkan cache: `php artisan cache:clear`. |
| Perubahan konfigurasi `.env` tidak terbaca           | Jalankan **`php artisan config:clear`**.                                                                    |
| Tombol "Cetak PDF" tidak menghasilkan PDF            | Pada dialog cetak browser, pilih tujuan **"Save as PDF"**. Fitur ini memang memakai cetak bawaan browser.   |

---

## 17. Lampiran A — Menjalankan dengan Docker

Sebagai alternatif XAMPP, proyek ini menyertakan konfigurasi Docker agar **database (MariaDB) dan aplikasi berjalan dengan satu perintah** — tanpa perlu memasang PHP, Composer, Node, atau MySQL secara terpisah.

### A.1. Prasyarat

- **Docker Desktop** (Windows/Mac) atau Docker Engine + Compose v2 (Linux). Pastikan Docker sedang berjalan.

### A.2. Berkas yang terlibat

| Berkas                  | Fungsi                                                                              |
|-------------------------|-------------------------------------------------------------------------------------|
| `Dockerfile`            | Image aplikasi: PHP 8.2 + ekstensi, Composer, dan Node 20.                          |
| `docker-compose.yml`    | Mendefinisikan 3 layanan: `app`, `db` (MariaDB 11), dan `phpmyadmin`.               |
| `docker/entrypoint.sh`  | Skrip bootstrap: install dependensi → build aset → migrasi → seed → jalankan server. |
| `.env.docker`           | Template `.env` yang dipakai bila belum ada `.env`.                                 |
| `.dockerignore`         | Memperkecil konteks build.                                                          |

### A.3. Menjalankan

Dari folder proyek:

```bash
docker compose up -d --build
```

Pada **boot pertama** container `app` otomatis melakukan (butuh beberapa menit):

1. Memakai `.env.docker` sebagai `.env` di dalam container (berkas `.env` XAMPP Anda tidak tersentuh).
2. `composer install` — memasang dependensi PHP.
3. `php artisan key:generate` — membuat application key.
4. `npm install` lalu `npm run build` — membangun aset frontend.
5. Menunggu database siap, lalu `php artisan migrate`.
6. `php artisan db:seed` — **hanya jika database masih kosong** (agar restart tidak mereset data).
7. Menjalankan `php artisan serve` di port 8000.

> Boot berikutnya jauh lebih cepat karena `vendor`, `node_modules`, dan data DB sudah tersimpan di volume.

### A.4. Alamat akses

| Layanan          | URL / Host                  | Keterangan                                            |
|------------------|-----------------------------|-------------------------------------------------------|
| Aplikasi         | `http://localhost:8000`     | Halaman utama → diarahkan ke login.                   |
| phpMyAdmin       | `http://localhost:8080`     | GUI database (host: `db`, user `labreserve` / `secret`, atau `root` / `secret`). |
| Database (luar)  | `localhost:3307`            | Untuk klien DB eksternal. Port **3307** dipakai agar tidak bentrok dengan MySQL XAMPP. |

Akun default sama dengan [bagian 8](#8-akun-default) (admin `admin@ukrida.ac.id` / `Admin@123`, dst.).

### A.5. Perintah yang berguna

```bash
docker compose logs -f app           # Lihat log aplikasi (pantau proses boot pertama)
docker compose exec app php artisan migrate   # Jalankan perintah artisan di dalam container
docker compose exec app npm run build         # Bangun ulang aset setelah ubah CSS/JS
docker compose exec app bash         # Masuk ke shell container app
docker compose stop                  # Hentikan tanpa menghapus
docker compose down                  # Hentikan & hapus container (data DB tetap aman di volume)
docker compose down -v               # Hentikan & HAPUS data DB (reset total — migrasi+seed ulang)
```

### A.6. Catatan

- **Sumber kode di-mount langsung** (`./` → `/var/www/html`), sehingga perubahan file PHP/Blade langsung tampak tanpa rebuild. Untuk perubahan **CSS/JS/Tailwind**, jalankan ulang `npm run build` (lihat A.5) karena aset dibangun sekali saat boot.
- Container memakai berkas **`.env.docker`** (di-mount sebagai `.env` di dalam container, hanya-baca) yang sudah berisi `DB_HOST=db`. Berkas `.env` milik XAMPP Anda (yang memakai `127.0.0.1`) **tidak tersentuh** dan tetap aman. Ini penting karena `php artisan serve` tidak meneruskan variabel `DB_*` dari environment ke server bawaannya — jadi konfigurasi DB harus ada di `.env` container.
- Kata sandi default DB (`secret`) hanya untuk pemakaian lokal. Ganti untuk lingkungan yang dapat diakses publik.

---

*Dokumentasi ini mengikuti kode aplikasi per Juni 2026. Jika ada perubahan fitur, perbarui dokumen ini agar tetap akurat.*
