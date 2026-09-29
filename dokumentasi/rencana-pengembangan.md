# Rencana Pengembangan dan Ruang Lingkup

FlowFix diajukan sebagai ide awal hackathon dengan fokus pada administrasi mahasiswa. Pengembangan direncanakan bertahap agar nilai utama solusi dapat dipelajari sebelum cakupannya diperluas.

## Fokus Awal

Layanan utama adalah pengajuan magang pada satu program studi atau departemen. Alurnya mencakup pengisian formulir, unggahan dokumen, pemeriksaan awal, peninjauan manusia, permintaan revisi, usulan koreksi, pemeriksaan ulang, pengiriman kembali, dan persetujuan akhir.

Satu layanan sederhana berupa surat keterangan mahasiswa aktif direncanakan memakai mesin konfigurasi yang sama untuk menilai apakah pendekatan dapat digunakan ulang.

## Ruang Lingkup Prototipe

- Identitas pengguna dan peran mahasiswa, peninjau, serta administrator.
- Pemisahan data berdasarkan institusi dan kewenangan pengguna.
- Konfigurasi formulir, dokumen wajib, SOP, dan satu tahap persetujuan.
- **Submission Assistant** sebagai antarmuka percakapan utama mahasiswa: deteksi intent, panduan persyaratan, pengumpulan informasi bertahap, permintaan dokumen, dan penjelasan temuan.
- Document Pre-Check untuk memeriksa kelengkapan dan konsistensi dokumen berdasarkan bukti.
- Reviewer Summary untuk menyajikan ringkasan pengajuan kepada peninjau.
- Revision Planner dengan bukti pendukung dan persetujuan koreksi.
- State pengajuan terstruktur di backend, terpisah dari memori percakapan LLM.
- Riwayat versi pengajuan dan tindakan pengguna.
- Jalur pemeriksaan manusia ketika AI tidak dapat memastikan hasil.

Penyusunan alur visual yang kompleks, banyak tahap persetujuan, pembayaran, dan integrasi sistem kampus ditempatkan pada pengembangan lanjutan.

## Tahapan yang Direncanakan

| Tahap | Fokus | Hasil yang Diharapkan |
| --- | --- | --- |
| 1. Pemahaman masalah | Wawancara dan pengamatan proses administrasi | Kebutuhan pengguna, masalah prioritas, dan kondisi pembanding |
| 2. Perancangan layanan | Aturan, formulir, peran, serta pengelolaan data | Batas fitur dan rancangan proses yang disepakati |
| 3. Pengembangan inti | Pengajuan, dokumen, peninjauan, dan konfigurasi layanan | Alur administrasi dasar |
| 4. Integrasi AI | Langflow, penghubung aplikasi, dan pengujian dengan IBM Bob | Pemeriksaan, ringkasan, serta pemulihan revisi yang terhubung |
| 5. Evaluasi | Kegunaan, ketepatan hasil, hak akses, dan aksesibilitas | Temuan perbaikan serta pengukuran dampak awal |
| 6. Perluasan | Layanan tambahan dan kebutuhan institusi lain | Dasar untuk penggunaan yang lebih luas |

Durasi setiap tahap akan disesuaikan dengan kapasitas tim, akses ke calon pengguna, dan jadwal program. Prioritasnya adalah menyelesaikan alur utama sebelum menambah fitur pendukung.

## Skalabilitas

Perluasan layanan menggunakan konfigurasi formulir dan persyaratan yang dapat digunakan ulang. Perluasan institusi menggunakan rancangan multi-tenant dengan pengguna, SOP, dokumen, dan pengajuan yang terpisah.

Jika volume meningkat, pemrosesan dokumen dapat dikembangkan menjadi antrean pekerjaan dengan penambahan kapasitas pemrosesan. Pemantauan biaya AI, waktu respons, penyimpanan, dan batas penggunaan membantu menentukan kapasitas yang sesuai.

Jumlah pengguna yang dapat dilayani akan ditentukan melalui pengujian beban. Penambahan institusi juga memerlukan proses penyiapan layanan, dukungan, serta pengelolaan data yang konsisten.

## Pengembangan Lanjutan

- Layanan cuti akademik, izin penelitian, dan administrasi beasiswa.
- Notifikasi pengajuan otomatis (Notification Composer).
- Asisten tanya jawab lanjutan di luar ruang lingkup Submission Assistant.
- Analitik waktu layanan dan pola revisi.
- Integrasi sistem informasi akademik dan autentikasi kampus.
- Notifikasi, tanda tangan digital, dan kebutuhan operasional institusi.
- Pilihan pemasangan pada infrastruktur institusi sesuai kebutuhan.

## Kelayakan Ide

Pendekatan bertahap membatasi kompleksitas pada satu proses yang jelas, memakai ulang mesin layanan, dan menempatkan AI pada pekerjaan interpretasi dokumen serta revisi. Kelayakan lebih lanjut akan dinilai dari kebutuhan pengguna, keandalan analisis, biaya operasional, dan kemudahan adopsi.

Dokumen terkait: [arsitektur](arsitektur-sistem.md), [validasi](rencana-validasi.md), [model bisnis](model-bisnis.md), dan [AI yang bertanggung jawab](prinsip-ai.md).
