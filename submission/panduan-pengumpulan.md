# Panduan Pengumpulan FlowFix

Paket ini disiapkan untuk pengajuan ide awal: dokumentasi konsep, pitch deck, dan screenshot prototipe antarmuka. Semua data, dokumen, temuan AI, serta tindakan pengajuan pada prototipe merupakan simulasi lokal.

## 1. Project File / Link

Masukkan tautan repositori GitHub publik yang memuat [README FlowFix](../readme.md), dokumentasi, pitch deck, dan screenshot. Alternatifnya, gunakan tautan berkas dokumentasi atau presentasi di Google Drive yang dapat dilihat reviewer.

Alamat repositori GitHub belum dikonfigurasi pada proyek lokal ketika paket ini disiapkan. Gunakan alamat repositori yang sebenarnya setelah diunggah. Jalur berkas lokal pada komputer bukan tautan yang dapat diakses reviewer. Periksa tautan melalui jendela privat tanpa masuk ke akun pemilik.

Jika kolom menerima deskripsi bersama tautan, teks pengantarnya dapat menggunakan:

> FlowFix — platform administrasi kampus berbantuan AI. Dokumentasi mencakup masalah, solusi, pengguna, fitur, rancangan integrasi Langflow–IBM Bob, target dampak, serta prototipe antarmuka dengan data simulasi.

## 2. Pitching Deck

Unggah [FlowFix-Pitch-Deck.pdf](../presentasi/FlowFix-Pitch-Deck.pdf). Presentasi berisi delapan slide dan berada di bawah batas 10 MB.

Versi yang dapat diedit tersedia dalam [PowerPoint](../presentasi/FlowFix-Pitch-Deck.pptx). Unggah satu berkas sesuai batas kolom; PDF disiapkan untuk menjaga tata letak.

## 3. Project / Prototype Screenshot

Unggah lima berkas PNG berikut secara terpisah. Seluruhnya berada di bawah batas 10 MB per berkas.

| Berkas | Isi yang Ditunjukkan |
| --- | --- |
| [01-layanan-kampus.png](screenshot/01-layanan-kampus.png) | Portal mahasiswa, layanan yang direncanakan, dan contoh pengajuan |
| [02-pemeriksaan-awal.png](screenshot/02-pemeriksaan-awal.png) | Dokumen, temuan simulasi, aturan terkait, dan pilihan pemeriksaan manusia |
| [03-usulan-revisi.png](screenshot/03-usulan-revisi.png) | Komentar peninjau, bukti terbaru, serta usulan perubahan yang perlu disetujui |
| [04-rancangan-integrasi.png](screenshot/04-rancangan-integrasi.png) | Diagram hubungan Bob–MCP–Langflow dan alur penggunaan aplikasi |
| [05-hasil-perbaikan.png](screenshot/05-hasil-perbaikan.png) | Hasil koreksi simulasi, riwayat tindakan, dan tombol pengiriman ulang terpisah |

Screenshot diambil dari prototipe lokal yang dapat dibuka di peramban. Label simulasi terlihat pada setiap layar. Diagram integrasi merupakan rancangan FlowFix, bukan screenshot aplikasi Langflow atau sesi IBM Bob. Paket ini menjelaskan konsep dan interaksi antarmuka; bukti eksekusi integrasi AI belum tersedia.

## Membuka Prototipe

Buka [prototipe/index.html](../prototipe/index.html) pada peramban setelah repositori diunduh. Prototipe berjalan tanpa server dan tanpa koneksi AI.

Interaksi yang tersedia:

- Berpindah antara layanan, pemeriksaan awal, revisi, dan rancangan integrasi.
- Melengkapi contoh dokumen untuk memperbarui hasil pemeriksaan simulasi.
- Menyetujui usulan sebelum menerapkan perubahan.
- Memeriksa hasil koreksi dan mengirim ulang melalui tindakan terpisah.

Perubahan hanya berlaku selama sesi halaman dan akan direset ketika halaman dimuat ulang. Tidak ada data yang dikirim ke institusi atau layanan eksternal.
