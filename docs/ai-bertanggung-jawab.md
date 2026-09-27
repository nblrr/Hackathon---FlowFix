# Prinsip AI yang Bertanggung Jawab

FlowFix dirancang untuk membantu interpretasi dokumen dan persiapan revisi dengan pengawasan manusia. Prinsip berikut menjadi dasar rancangan fitur, pengelolaan data, dan evaluasi.

## Kendali Pengguna dan Peninjau

Mahasiswa perlu mengetahui apa yang ditemukan AI, aturan yang digunakan, sumber bukti, dan perubahan yang diusulkan. Mahasiswa dapat menerima, mengedit, menolak usulan, atau meminta pemeriksaan manusia.

Penerapan koreksi memerlukan persetujuan pengguna. Pengiriman ulang dilakukan secara eksplisit oleh mahasiswa. Keputusan administratif akhir berada pada staf atau dosen yang berwenang.

## Transparansi dan Ketidakpastian

Temuan serta ringkasan akan diberi penanda sebagai hasil bantuan AI. Setiap temuan perlu merujuk pada aturan dan bukti yang tersedia. Jika sumber tidak terbaca atau saling bertentangan, sistem harus menjelaskan keterbatasannya dan mengarahkan kasus ke manusia.

Hasil pemeriksaan tanpa temuan bukan jaminan keaslian dokumen maupun persetujuan akhir. Peninjau tetap dapat membuka dokumen asli dan melakukan verifikasi tambahan.

## Privasi dan Pembatasan Data

Pengumpulan data dibatasi pada kebutuhan layanan yang dipilih. Pengujian awal menggunakan data sintetis. Penggunaan data nyata perlu mempertimbangkan tujuan pemrosesan, kewenangan akses, penyedia AI, lama penyimpanan, serta mekanisme koreksi dan penghapusan yang disepakati institusi.

Rancangan akses mencakup:

- Pemisahan data antarinstansi.
- Pembatasan mahasiswa pada pengajuannya sendiri.
- Akses peninjau berdasarkan kewenangan dan penugasan.
- Pengiriman hanya konteks yang diperlukan ke layanan AI.
- Penyimpanan dokumen secara privat dan pembatasan informasi sensitif pada catatan sistem.

## Keadilan dan Aksesibilitas

Dokumen dengan format berbeda dapat menimbulkan perbedaan kemampuan ekstraksi. Keterbatasan tersebut harus dijelaskan dan tersedia jalur pemeriksaan manusia agar mahasiswa tidak dirugikan oleh kegagalan teknis.

Antarmuka direncanakan menggunakan bahasa yang mudah dipahami, label formulir yang jelas, navigasi papan ketik, dukungan pembaca layar, serta penanda status yang tidak hanya mengandalkan warna. Tampilan ponsel menjadi bagian dari pertimbangan akses.

## Risiko dan Mitigasi

| Risiko | Mitigasi yang Direncanakan |
| --- | --- |
| AI menghasilkan temuan tanpa bukti | Wajibkan referensi aturan dan sumber; arahkan bukti yang tidak cukup ke manusia |
| Temuan keliru menghambat pengajuan | Sediakan jalur keberatan dan pemeriksaan manusia |
| Perubahan diterapkan tanpa izin | Batasi tindakan di backend dan minta persetujuan eksplisit |
| Usulan lama menimpa data baru | Periksa versi pengajuan dan nilai awal sebelum menerapkan perubahan |
| Kebocoran antarinstansi atau pengguna | Terapkan pemeriksaan akses pada data, dokumen, konteks AI, dan hasil |
| Instruksi berbahaya di dokumen | Perlakukan isi dokumen sebagai data; tindakan tetap dibatasi oleh aplikasi |
| Aturan layanan berubah | Simpan versi SOP dan konfigurasi yang digunakan pada setiap pengajuan |
| Layanan AI gagal | Pertahankan data pengguna dan sediakan pemeriksaan manual |

## Batas Kemampuan

Recovery Agent dapat mengusulkan koreksi formulir atau menyarankan pengguna memperoleh dokumen yang diperbaiki oleh pihak berwenang. Agent tidak diberi kewenangan memalsukan dokumen, tanda tangan, kelayakan akademik, atau persetujuan.

Keamanan, keadilan, dan efektivitas perlu dievaluasi melalui pengujian serta masukan pengguna. Prinsip ini merupakan komitmen rancangan, bukan klaim sertifikasi atau kepatuhan yang sudah diperoleh.

Lihat [rencana validasi](validasi.md) untuk pendekatan evaluasinya.
