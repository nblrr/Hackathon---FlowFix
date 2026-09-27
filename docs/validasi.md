# Rencana Validasi Masalah dan Dampak

Validasi FlowFix direncanakan untuk mengetahui apakah masalah yang dipilih cukup penting, apakah solusi membantu pengguna, dan apakah institusi bersedia mengadopsinya. Seluruh kegiatan dalam dokumen ini merupakan rencana.

## Pertanyaan Utama

- Seberapa sering mahasiswa mengalami kekurangan dokumen atau ketidaksesuaian informasi?
- Bagian mana dari komentar revisi yang sulit dipahami?
- Berapa banyak waktu aktif staf digunakan untuk pemeriksaan berulang?
- Apakah pemeriksaan awal dan usulan koreksi membantu menyelesaikan pengajuan?
- Siapa yang mengambil keputusan pembelian dan apa syarat adopsi di institusi?

## Wawancara Calon Pengguna

Rencana awal melibatkan lima mahasiswa, dua staf atau dosen peninjau, dan satu calon pengambil keputusan institusional. Jumlah tersebut merupakan sasaran perekrutan awal.

Mahasiswa akan ditanya tentang pengalaman pengajuan, persyaratan yang membingungkan, revisi, dan cara memperoleh bantuan. Peninjau akan ditanya tentang kesalahan yang sering ditemukan, pemeriksaan yang memerlukan pertimbangan manusia, dan beban kerja. Calon pembeli akan ditanya tentang kebutuhan, anggaran, proses pengadaan, serta persyaratan pengelolaan data.

Waktu aktif mengerjakan tugas akan dibedakan dari waktu menunggu persetujuan. Catatan dan kutipan akan digunakan dengan izin peserta serta disajikan tanpa identitas pribadi.

## Pendekatan Pengujian

Peserta akan mengerjakan tugas pengajuan dan revisi dengan dua cara: proses pembanding menggunakan formulir serta instruksi tertulis, dan proses berbantuan FlowFix. Kasus sintetis dibuat setara dalam tingkat kesulitan. Urutan pengerjaan akan digilir untuk mengurangi pengaruh pengalaman dari tugas sebelumnya.

Penilaian mencakup waktu aktif, kelengkapan hasil, ketepatan revisi, pemahaman pengguna, serta keputusan peninjau. Kegagalan dan tugas yang tidak selesai tetap dicatat. Ukuran sampel, cara perekrutan, serta keterbatasan akan disertakan dalam pelaporan hasil.

## Indikator dan Target Awal

Target berikut merupakan sasaran evaluasi, bukan hasil yang sudah dicapai.

| Indikator | Cara Mengukur | Target Awal |
| --- | --- | --- |
| Waktu pengajuan mahasiswa | Median waktu aktif dari membaca persyaratan hingga mengirim | Berkurang minimal 20% tanpa menambah kesalahan tersisa |
| Waktu pemeriksaan peninjau | Median waktu aktif memeriksa tugas setara | Berkurang minimal 20% tanpa penurunan ketepatan keputusan |
| Keberhasilan revisi | Jumlah revisi yang benar pada pengiriman ulang pertama dibagi seluruh tugas revisi yang dicoba | Minimal 80% |
| Pemahaman koreksi | Proporsi peserta yang dapat menjelaskan perubahan dan sumbernya tanpa bantuan | Minimal 80% |
| Kesalahan tersisa | Jumlah pelanggaran persyaratan saat pengajuan dikirim | Lebih sedikit daripada proses pembanding |
| Ketepatan temuan AI | Temuan yang benar dibagi seluruh temuan masalah | Minimal 90% pada kumpulan kasus uji |
| Kelengkapan deteksi AI | Masalah yang terdeteksi dengan benar dibagi seluruh masalah yang sudah diberi label | Minimal 80% pada kumpulan kasus uji |

Persentase akan dilaporkan bersama jumlah kasus atau pesertanya. Hasil dari sampel kecil digunakan untuk pembelajaran awal dan perbaikan, bukan untuk menyimpulkan keberhasilan pada seluruh perguruan tinggi.

## Keandalan dan Aksesibilitas

Pengujian AI direncanakan menggunakan sedikitnya 20 kasus sintetis dengan jawaban acuan. Kasus mencakup dokumen lengkap, informasi hilang, tanggal berbeda, aturan ambigu, bukti terbaru, dan dokumen tidak terbaca. Kasus untuk menyesuaikan instruksi AI dipisahkan dari kasus evaluasi.

Pengujian juga mencakup akses antarinstansi, usulan koreksi yang sudah kedaluwarsa, kegagalan layanan AI, dan instruksi berbahaya di dalam dokumen. Kesalahan yang memungkinkan akses atau tindakan tanpa wewenang harus diperbaiki sebelum penggunaan lebih luas.

Aksesibilitas akan diperiksa melalui navigasi papan ketik, pembaca layar, tampilan ponsel, pesan kesalahan yang jelas, dan jalur pemeriksaan manusia. Variasi nama serta tata letak dokumen dengan isi setara akan digunakan untuk menilai perbedaan kesalahan AI.

## Pemanfaatan Hasil

Temuan wawancara akan menentukan prioritas masalah. Hasil pengujian akan digunakan untuk memperbaiki alur, instruksi AI, dan batas fitur. Jika penghematan waktu kecil tetapi pemahaman revisi meningkat, manfaat tersebut akan dilaporkan sesuai hasilnya.

Hubungan antara manfaat, biaya, dan kesediaan membayar dijelaskan dalam [model bisnis](bisnis.md).
