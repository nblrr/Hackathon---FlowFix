# Rencana Pengembangan dan Validasi

Dokumen ini menggabungkan arah pengembangan FlowFix dan rencana validasi dampaknya. Pengembangan direncanakan bertahap agar nilai utama solusi dapat dipelajari sebelum cakupannya diperluas.

## Status Implementasi Saat Ini

| Komponen | Status |
| --- | --- |
| Backend Laravel 12 + PHP 8.2 + SQLite | ✅ Berjalan — autentikasi Sanctum, 4 migrations, REST API lengkap |
| Frontend React 19 + Vite + TypeScript + Tailwind | ✅ Berjalan — antarmuka mahasiswa dan peninjau |
| 4 Langflow AI Flows | ✅ Berjalan end-to-end — chat AI, pemeriksaan dokumen, ringkasan peninjau, revision planner |
| Ekstraksi PDF | ✅ Berjalan — pypdf via subprocess, strict=False |
| Test Pack (24 kasus sintetis + PDF) | ✅ Tersedia di `FlowFix-Test-Pack/` |
| Integration test runner | ✅ `tests/run_tests.py` dengan 11 kasus via `tests/cases.json` |

## Fokus Awal

Layanan utama adalah pengajuan magang pada satu program studi atau departemen. Alurnya mencakup pengisian formulir, unggahan dokumen, pemeriksaan awal, peninjauan manusia, permintaan revisi, usulan koreksi, pemeriksaan ulang, pengiriman kembali, dan persetujuan akhir.

## Ruang Lingkup Prototipe (Sudah Diimplementasi)

- Identitas pengguna dan peran mahasiswa serta peninjau.
- **Submission Assistant** — antarmuka percakapan utama: deteksi intent, panduan persyaratan, pengumpulan informasi bertahap, penjelasan temuan.
- **Document Pre-Check** — memeriksa kelengkapan dan konsistensi dokumen berdasarkan bukti, output JSON tervalidasi schema.
- **Reviewer Summary** — menyajikan ringkasan pengajuan kepada peninjau.
- **Revision Planner** — root cause analysis dan usulan koreksi dengan persetujuan mahasiswa.
- State pengajuan terstruktur di backend, terpisah dari memori percakapan LLM.
- Riwayat versi pengajuan dan audit events.
- Serialisasi aksi per pengajuan (file lock) untuk mencegah race condition.
- Jalur pemeriksaan manusia ketika AI tidak dapat memastikan hasil (`NEEDS_HUMAN_CHECK`).

## Tahapan Pengembangan

| Tahap | Fokus | Status |
| --- | --- | --- |
| 1. Pemahaman masalah | Wawancara dan pengamatan proses administrasi | Direncanakan |
| 2. Perancangan layanan | Aturan, formulir, peran, pengelolaan data | ✅ Selesai (SOP Simulasi v1.0) |
| 3. Pengembangan inti | Pengajuan, dokumen, peninjauan, konfigurasi layanan | ✅ Selesai |
| 4. Integrasi AI | Langflow, penghubung aplikasi, pengujian | ✅ Selesai — 4 flows berjalan end-to-end |
| 5. Evaluasi | Kegunaan, ketepatan hasil, aksesibilitas | Direncanakan |
| 6. Perluasan | Layanan tambahan, institusi lain | Direncanakan |

## Pengembangan Lanjutan

- Layanan cuti akademik, izin penelitian, dan administrasi beasiswa.
- Notifikasi pengajuan otomatis.
- Analitik waktu layanan dan pola revisi.
- Integrasi sistem informasi akademik dan autentikasi kampus.
- Tanda tangan digital dan kebutuhan operasional institusi.
- Pilihan pemasangan pada infrastruktur institusi.
- Skalabilitas multi-tenant dan antrean pekerjaan untuk volume tinggi.

---

## Rencana Validasi Dampak

Validasi direncanakan untuk mengetahui apakah masalah yang dipilih cukup penting, apakah solusi membantu pengguna, dan apakah institusi bersedia mengadopsinya.

### Pertanyaan Utama

- Seberapa sering mahasiswa mengalami kekurangan dokumen atau ketidaksesuaian informasi?
- Bagian mana dari komentar revisi yang sulit dipahami?
- Berapa banyak waktu aktif staf digunakan untuk pemeriksaan berulang?
- Apakah pemeriksaan awal dan usulan koreksi membantu menyelesaikan pengajuan?

### Indikator dan Target Awal

Target berikut merupakan sasaran evaluasi, bukan hasil yang sudah dicapai.

| Indikator | Cara Mengukur | Target Awal |
| --- | --- | --- |
| Waktu pengajuan mahasiswa | Median waktu aktif dari membaca persyaratan hingga mengirim | Berkurang minimal 20% |
| Waktu pemeriksaan peninjau | Median waktu aktif memeriksa tugas setara | Berkurang minimal 20% |
| Keberhasilan revisi | Revisi benar pada pengiriman ulang pertama | Minimal 80% |
| Ketepatan temuan AI | Temuan benar dibagi seluruh temuan masalah | Minimal 90% pada kasus uji |
| Kelengkapan deteksi AI | Masalah terdeteksi dibagi seluruh masalah berlabel | Minimal 80% pada kasus uji |

### Keandalan dan Keamanan

Pengujian AI menggunakan 24 kasus sintetis di `FlowFix-Test-Pack/` dengan jawaban acuan. Kasus mencakup dokumen lengkap, informasi hilang, tanggal berbeda, aturan ambigu, bukti terbaru, dokumen tidak terbaca, dan dokumen terkunci/rusak.

Pengujian juga mencakup kegagalan layanan AI, instruksi berbahaya di dalam dokumen (prompt injection), dan akses tanpa wewenang. Aksesibilitas diperiksa melalui navigasi papan ketik, pembaca layar, dan tampilan ponsel.

Dokumen terkait: [arsitektur](arsitektur-sistem.md), [model bisnis](model-bisnis.md), [prinsip AI](prinsip-ai.md).
