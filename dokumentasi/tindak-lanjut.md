# Status dan Tindak Lanjut

Dokumen ini membedakan pekerjaan pengumpulan ide dari implementasi sistem. Status dicatat pada 29 September 2026; diperbarui setelah perbaikan teknis awal.

## Kondisi Saat Ini

| Bagian | Status |
| --- | --- |
| README dan dokumentasi konsep | Tersedia dalam bahasa Indonesia |
| Presentasi | Delapan slide; tersedia PDF dan PowerPoint |
| Prototipe antarmuka | Simulasi HTML lokal; belum terhubung ke backend atau AI |
| Flow Langflow (7 JSON) | Tersedia; Submission Assistant diperbaiki sebagai alur utama mahasiswa; tiga flow dokumen inti diperbaiki — model diperbarui, `should_store_message: false`, schema output didefinisikan, `mcp_enabled: true` di ekspor |
| Schema output | Tersedia di `langflow/schemas/` untuk tiga flow inti |
| Dataset testing | Tersedia di `tests/cases.json` (7 kasus sintetis) dengan test runner `tests/run_tests.py` |
| Koneksi MCP lokal | Handshake dan daftar alat berhasil pada proyek `starter_project` |
| Pemanggilan FlowFix melalui MCP | **Belum terverifikasi** — flow perlu diimpor ke instance Langflow dan MCP diaktifkan manual dari UI |
| Backend, basis data, dan integrasi aplikasi | Direncanakan |

## Untuk Pengumpulan Ide Awal

1. Baca [README](../README.md) dan pastikan masalah, pengguna, solusi, serta target dampak mewakili ide yang akan diajukan.
2. Tinjau [presentasi PDF](../pengumpulan/presentasi/flowfix.pdf) dan pilih [tangkapan layar](../pengumpulan/panduan.md#3-project--prototype-screenshot) sesuai ketentuan panitia. Pertahankan keterangan simulasi dan rancangan.
3. Periksa perubahan repositori, kemudian commit dan push berkas publik yang sudah dirapikan. `.bob/`, kredensial, dan `catatan-pembicara.md` diabaikan Git.
4. Buka [repositori GitHub](https://github.com/nblrr/Hackathon---FlowFix) tanpa masuk ke akun pemilik untuk memastikan reviewer dapat mengaksesnya.
5. Isi kolom pengumpulan menggunakan [panduan pengumpulan](../pengumpulan/panduan.md).

Backend lengkap belum diperlukan untuk menjelaskan ide awal. Jangan menyatakan integrasi atau dampak sudah terbukti sebelum pengujiannya tersedia.

## Untuk Melanjutkan Integrasi Langflow

1. Di IBM Bob, restart/reconnect server `lf-hackathon` agar konfigurasi koneksi dimuat ulang.
2. Tentukan proyek FlowFix di instance Langflow yang terhubung.
3. Impor `langflow/pemeriksaan-dokumen.json`. Di Langflow UI, pilih model aktif (`gemini-1.5-flash` atau model yang tersedia) dan isi Google API Key.
4. Jalankan dengan data sintetis dari `tests/cases.json` (TC-01 sampai TC-05). Verifikasi output JSON sesuai schema di `langflow/schemas/pemeriksaan-dokumen.schema.json`.
5. Aktifkan MCP di flow tersebut: pastikan `mcp_enabled` toggle aktif dan `endpoint_name` = `check_submission`. Aktifkan pada project settings.
6. Restart koneksi MCP di Bob. Verifikasi alat `check_submission` muncul di daftar alat.
7. Uji satu pemanggilan dari Bob menggunakan TC-01 sebagai input. Catat hasilnya.
8. Setelah flow pertama stabil, lanjutkan dengan `ringkasan-peninjau.json` dan `pemulihan-revisi.json`. Gunakan TC-06 dan TC-07 untuk pengujian.
9. Untuk pengujian otomatis: set environment variable dan jalankan `python tests/run_tests.py`.

## Untuk Membangun Sistem

Backend harus memeriksa hak akses, kelengkapan wajib, versi dokumen, serta perubahan status. Hasil AI divalidasi sebelum ditampilkan atau digunakan. Proposal revisi memerlukan persetujuan mahasiswa, dan pengiriman ulang tetap merupakan tindakan terpisah.

Prioritaskan Submission Assistant dan tiga alur dokumen inti. Submission Assistant adalah titik masuk percakapan utama mahasiswa — bukan pengembangan lanjutan. Penyusunan notifikasi berada pada pengembangan lanjutan. Seluruh berkas berada langsung di `langflow/`, dengan fungsi dan status pada [daftar flow](../langflow/README.md#daftar-flow). Audit Record Generator dan Submission Router tetap berstatus eksperimen; keduanya tidak menjadi pengendali proses administratif.

Rincian: [arsitektur](arsitektur-sistem.md), [audit Langflow](audit-langflow.md), dan [rencana validasi](rencana-validasi.md).
