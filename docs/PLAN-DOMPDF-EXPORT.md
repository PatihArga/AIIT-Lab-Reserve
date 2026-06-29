# Rencana Implementasi: Server-Side PDF Export dengan Dompdf

## Latar Belakang

Fitur ekspor PDF saat ini menggunakan `window.print()` yang mengandalkan dialog "Print to PDF" bawaan browser/OS. Dosen pembimbing menilai pendekatan ini kurang universal karena:

1. Bergantung pada fitur "Microsoft Print to PDF" yang tidak tersedia di semua perangkat
2. Tidak melakukan auto-download — pengguna harus memilih printer dan nama file secara manual
3. Hasil format tidak konsisten antar browser dan sistem operasi

**Solusi:** Ganti `window.print()` dengan server-side PDF generation menggunakan **laravel-dompdf** (`barryvdh/laravel-dompdf`), sehingga klik tombol langsung men-download file `.pdf` dengan nama otomatis.

---

## Cakupan Perubahan

Dua fitur admin yang terpengaruh:

| Fitur | Rute Saat Ini | Tombol Saat Ini | Perubahan |
|---|---|---|---|
| **Laporan & Analitik** | `GET /admin/reports` | `<button onclick="window.print()">Cetak PDF</button>` | Tambah rute `GET /admin/reports/export-pdf` → download `laporan-AIIT-{from}_{to}.pdf` |
| **Audit Log** | `GET /admin/audit-log` | `<button onclick="window.print()">Cetak PDF</button>` | Tambah rute `GET /admin/audit-log/export-pdf` → download `audit-log-AIIT-{date}.pdf` |

---

## File yang Akan Diubah/Dibuat

### Dependensi

#### [INSTALL] barryvdh/laravel-dompdf

```bash
composer require barryvdh/laravel-dompdf
```

> Package ini auto-discovers, sehingga tidak perlu register service provider manual.

---

### Controller

#### [MODIFY] `app/Http/Controllers/Admin/AdminReportController.php`

- Tambah method `exportPdf(Request $request)`:
  - Reuse logic `resolvePeriod()` yang sudah ada untuk parsing tanggal
  - Panggil `ReportService::generate($from, $to)` yang sama
  - Render view khusus PDF (`admin.reports.pdf`)
  - Return response `->download('AIIT Report - {download_date}.pdf')`

#### [MODIFY] `app/Http/Controllers/Admin/AdminAuditLogController.php`

- Tambah method `exportPdf(Request $request)`:
  - Reuse filter/query logic dari `index()` (direfaktor ke private method `buildQuery()`)
  - Ambil semua entries tanpa pagination (max 500 rows sebagai safety limit)
  - Render view khusus PDF (`admin.audit-log.pdf`)
  - Return response `->download('AIIT Audit-log - {download_date}.pdf')`

---

### View — Template PDF (Baru)

#### [NEW] `resources/views/admin/reports/pdf.blade.php`

Template standalone (tanpa layout `x-app-layout`) yang mereproduksi konten laporan dalam format cetak:
- Header: judul "Laporan Lab AIIT UKRIDA", periode, tanggal cetak
- KPI band: 4 metrik utama (total reservasi, tingkat pemakaian, pengguna aktif, rata-rata durasi)
- Tabel pemakaian per minggu (menggantikan CSS bar chart dengan tabel angka)
- Tabel distribusi kategori (menggantikan donut SVG dengan tabel persentase)
- Tabel top 5 pengguna
- Tabel penggunaan per komputer
- Tabel instalasi perangkat lunak (jika ada)
- Styling: inline CSS yang dompdf-compatible (no Tailwind, no flexbox, gunakan `<table>` layout)

#### [NEW] `resources/views/admin/audit-log/pdf.blade.php`

Template standalone untuk ekspor audit log:
- Header: judul "Audit Log Lab AIIT UKRIDA", filter yang diterapkan, tanggal cetak
- Statistik ringkas (total, hari ini, reservasi diproses, pengguna aktif)
- Tabel log: kolom Waktu, Aksi, Deskripsi, Aktor, Target, Perubahan
- Setiap baris menampilkan inline diff (before → after) jika ada
- Styling: inline CSS yang dompdf-compatible

---

### Routes

#### [MODIFY] `routes/web.php`

Tambahkan 2 rute baru di dalam group admin:

```php
Route::get('/reports/export-pdf', [AdminReportController::class, 'exportPdf'])
    ->name('reports.export-pdf');
Route::get('/audit-log/export-pdf', [AdminAuditLogController::class, 'exportPdf'])
    ->name('audit-log.export-pdf');
```

> **Penting:** Rute `export-pdf` harus ditempatkan SEBELUM rute yang menggunakan wildcard `{param}` (jika ada) agar tidak tertangkap oleh route parameter.

---

### View — Modifikasi Tombol

#### [MODIFY] `resources/views/admin/reports/index.blade.php`

- **Hapus** blok `@media print { ... }` dari `@push('styles')` (baris 3-19)
- **Ganti** tombol `<button onclick="window.print()">Cetak PDF</button>` (baris 123-126) menjadi link `<a>` ke rute export:

```html
<a href="{{ route('admin.reports.export-pdf', request()->query()) }}"
   class="btn-secondary btn-sm" title="Unduh laporan PDF">
    <!-- download icon -->
    Unduh PDF
</a>
```

#### [MODIFY] `resources/views/admin/audit-log/index.blade.php`

- **Hapus** blok `@media print { ... }` dari `@push('styles')` (baris 3-18)
- **Ganti** tombol `<button onclick="window.print()">Cetak PDF</button>` (baris 26-29) menjadi link `<a>`:

```html
<a href="{{ route('admin.audit-log.export-pdf', request()->query()) }}"
   class="btn-secondary btn-sm print:hidden" title="Unduh audit log PDF">
    <!-- download icon -->
    Unduh PDF
</a>
```

---

## Batasan Dompdf

Dompdf memiliki keterbatasan rendering CSS yang perlu diperhatikan:

| Fitur | Support Dompdf | Solusi di Template PDF |
|---|---|---|
| Tailwind CSS classes | ❌ Tidak | Gunakan inline CSS |
| CSS Flexbox | ⚠️ Partial | Gunakan `<table>` layout |
| CSS Grid | ❌ Tidak | Gunakan `<table>` layout |
| SVG donut chart | ⚠️ Partial | Ganti dengan tabel persentase |
| CSS bar chart | ❌ Tidak | Ganti dengan tabel angka + kolom bar sederhana (`width: N%`) |
| Google Fonts (@import) | ✅ Ya (lambat) | Gunakan font system: Arial, sans-serif |
| `border-radius`, `box-shadow` | ⚠️ Partial | Simplifikasi untuk cetak |
| `background-color` | ✅ Ya | Digunakan untuk header dan highlight |

---

## Nama File Download Otomatis

| Fitur | Format Nama File | Contoh |
|---|---|---|
| Laporan | `Laporan AIIT - {download_date}.pdf` | `Laporan AIIT - 29 Juni 2026.pdf` |
| Audit Log | `Audit-log AIIT - {download_date}.pdf` | `Audit-log AIIT - 29 Juni 2026.pdf` |

---

## Urutan Eksekusi

1. **Install dompdf** — `composer require barryvdh/laravel-dompdf`
2. **Buat template PDF** — `reports/pdf.blade.php` dan `audit-log/pdf.blade.php`
3. **Tambah method controller** — `exportPdf()` di kedua controller
4. **Tambah rute** — 2 rute GET baru di `web.php`
5. **Update tombol UI** — Ganti `window.print()` → `<a href>` di kedua view
6. **Hapus @media print** — Bersihkan CSS print yang tidak lagi dibutuhkan
7. **Verifikasi** — Test download PDF di browser, pastikan nama file otomatis dan konten lengkap

---

## Verifikasi

- [ ] `composer require barryvdh/laravel-dompdf` berhasil
- [ ] Klik "Unduh PDF" di halaman laporan → file `.pdf` langsung terdownload
- [ ] Klik "Unduh PDF" di halaman audit log → file `.pdf` langsung terdownload
- [ ] Nama file sesuai format otomatis (tidak perlu input manual)
- [ ] Konten PDF lengkap: semua data tabel, statistik, dan catatan muncul
- [ ] Filter laporan (periode/tanggal) ikut ter-forward ke PDF
- [ ] Filter audit log (aksi, aktor, tanggal, pencarian) ikut ter-forward ke PDF
- [ ] Tidak ada sisa kode `window.print()` atau `@media print` di kedua halaman
