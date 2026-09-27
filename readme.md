# FlowFix

> **Administrasi kampus berbantuan AI: cegah kesalahan pengajuan, pahami revisi, dan selesaikan proses dengan lebih jelas.**

FlowFix adalah konsep platform layanan perangkat lunak (SaaS) untuk membantu perguruan tinggi mengelola pengajuan administrasi mahasiswa. Platform ini menghubungkan formulir, dokumen, persyaratan, pemeriksaan, dan perbaikan dalam satu alur yang dapat dikonfigurasi.

**Tahap proyek: pengajuan ide awal hackathon.** Repositori ini memuat rancangan solusi dan arah pengembangan. Fitur serta integrasi teknologi yang dijelaskan merupakan rencana; target dampak akan diuji pada tahap prototipe.

## Latar Belakang Masalah

Pengajuan administrasi mahasiswa dapat memerlukan beberapa dokumen, aturan layanan yang berbeda, dan persetujuan manusia. Mahasiswa harus memahami persyaratan serta memastikan informasi pada formulir sesuai dengan dokumen pendukung. Ketika ada kekurangan atau ketidaksesuaian, pengajuan perlu direvisi.

Komentar revisi belum tentu langsung menjelaskan dokumen, kolom, atau aturan yang perlu diperbaiki. Mahasiswa dapat mengulang kesalahan, sementara staf dan dosen harus memeriksa informasi yang sama kembali. Kondisi ini berpotensi menambah beban pemeriksaan dan memperlambat penyelesaian layanan.

FlowFix berfokus pada **kesalahan pengajuan yang dapat dicegah dan kesulitan memahami langkah revisi**. Fokus awalnya adalah administrasi magang pada satu program studi atau departemen. Besarnya masalah pada calon institusi pengguna akan dipelajari melalui wawancara dan pengamatan proses.

## Solusi yang Diusulkan

FlowFix dirancang untuk membantu pengguna pada tiga tahap:

1. **Sebelum pengajuan:** AI memeriksa informasi terhadap persyaratan dan SOP, kemudian menunjukkan temuan beserta bukti yang perlu diperhatikan.
2. **Saat pemeriksaan:** peninjau memperoleh ringkasan fakta, referensi dokumen, dan bagian yang masih memerlukan verifikasi.
3. **Saat revisi:** agen pemulihan atau **Recovery Agent** menghubungkan komentar peninjau dengan aturan dan dokumen, lalu mengusulkan langkah koreksi yang spesifik.

Mahasiswa tetap meninjau perubahan dan mengirim sendiri pengajuannya. Staf atau dosen yang berwenang tetap mengambil keputusan administratif akhir.

## Pengguna Sasaran

| Pengguna | Kebutuhan yang Dibantu |
| --- | --- |
| Mahasiswa | Memahami persyaratan, menyiapkan pengajuan, mengetahui status, dan memperbaiki revisi |
| Staf administrasi | Memeriksa kelengkapan, menelusuri bukti, dan mengurangi pemeriksaan berulang |
| Dosen pembimbing atau peninjau akademik | Menilai pengajuan dan memberikan keputusan sesuai kewenangan |
| Administrator kampus | Mengatur formulir, persyaratan dokumen, SOP, dan tahap persetujuan |

Calon pelanggan institusionalnya adalah perguruan tinggi atau unit pengelola administrasi. Mahasiswa dan peninjau menjadi pengguna sehari-hari.

## Fitur Utama

| Fitur | Fungsi yang Direncanakan |
| --- | --- |
| **Alur layanan yang dapat dikonfigurasi** | Administrator menentukan formulir, dokumen wajib, referensi SOP, dan tahap persetujuan untuk setiap layanan |
| **Pemeriksaan awal dokumen berbantuan AI** | Mendeteksi kemungkinan kekurangan informasi atau ketidaksesuaian sebelum pengajuan dikirim |
| **Ringkasan untuk peninjau** | Menyajikan fakta penting, sumber dokumen, dan hal yang masih perlu diverifikasi |
| **Recovery Agent** | Mengusulkan koreksi berdasarkan komentar revisi, aturan, dan bukti; menampilkan nilai sebelum dan sesudah perubahan |
| **Pelacakan pengajuan dan riwayat tindakan** | Menampilkan status, versi dokumen, revisi, serta tindakan mahasiswa dan peninjau |

## Alur Penggunaan

Administrator mengatur layanan dan persyaratan. Mahasiswa kemudian memilih layanan, mengisi formulir, serta mengunggah dokumen. Setelah pemeriksaan awal, mahasiswa dapat memperbaiki temuan atau meminta peninjauan manusia sebelum melanjutkan pengajuan.

```mermaid
flowchart TD
    A[Pilih layanan dan isi formulir] --> B[Unggah dokumen]
    B --> C[Pemeriksaan awal berbantuan AI]
    C --> D[Tinjau temuan dan perbaiki atau minta pemeriksaan manusia]
    D --> E[Kirim pengajuan]
    E --> F[Peninjau membaca ringkasan dan dokumen asli]
    F --> G{Keputusan peninjau}
    G -->|Disetujui| H[Pengajuan selesai]
    G -->|Perlu revisi| I[Recovery Agent mengusulkan perbaikan]
    I --> J[Mahasiswa meninjau dan mengubah data]
    J --> K[Pemeriksaan ulang]
    K --> L[Mahasiswa mengirim kembali]
    L --> F
```

Pemeriksaan AI membantu menemukan masalah berdasarkan bukti yang tersedia. Jika dokumen tidak terbaca, aturan ambigu, atau layanan AI tidak tersedia, pengajuan diarahkan ke pemeriksaan manusia setelah memenuhi persyaratan isian dan berkas wajib.

## Nilai Inovasi

- **Pencegahan dan perbaikan dalam satu alur.** Temuan sebelum pengajuan dan bantuan setelah revisi menggunakan konteks pengajuan yang sama.
- **Koreksi yang spesifik dan dapat ditelusuri.** Usulan menghubungkan komentar peninjau, aturan, bukti dokumen, dan kolom yang terdampak.
- **Mesin layanan yang dapat digunakan ulang.** Formulir dan persyaratan dapat dikonfigurasi untuk beberapa layanan tanpa membuat aplikasi terpisah.

Contohnya, ketika surat magang diperbarui setelah pengajuan, Recovery Agent dapat membantu menemukan tanggal pada formulir yang perlu disesuaikan dan menjelaskan sumber perubahannya. Mahasiswa memutuskan koreksi tersebut sebelum pemeriksaan ulang.

Nilai tambah kombinasi ini akan dibandingkan dengan proses yang digunakan calon institusi pengguna.

## Rencana Penggunaan Langflow dan IBM Bob

**Langflow** direncanakan mengatur tiga alur AI: pemeriksaan awal, ringkasan peninjau, dan pemulihan revisi. Masukannya berupa data formulir, teks dokumen, SOP, serta komentar peninjau sesuai kebutuhan. Keluarannya berupa temuan, referensi bukti, ringkasan, atau usulan koreksi terstruktur.

**IBM Bob** direncanakan membantu pengembangan antarmuka, logika aplikasi, integrasi Langflow, serta pengujian. Hasil pekerjaannya tetap ditinjau oleh pengembang.

Hubungan langsung keduanya direncanakan melalui **Model Context Protocol (MCP)**: Bob memanggil alur Langflow menggunakan data uji sintetis, menerima hasil analisis, lalu membantu mengevaluasi hasil dan memperbaiki integrasi. Langflow mendukung penyediaan alur sebagai alat MCP, dan Bob mendukung koneksi ke server MCP. [Dokumentasi Langflow](https://docs.langflow.org/mcp-server), [dokumentasi IBM Bob](https://bob.ibm.com/docs/ide/configuration/mcp/mcp-in-bob).

```text
Pengembangan:
Pengembang → IBM Bob → MCP → Langflow → Hasil analisis
                ↑                              |
                └──── Evaluasi dan perbaikan ──┘

Penggunaan aplikasi:
Pengguna → Backend FlowFix → Langflow → Temuan atau usulan
        → Peninjauan pengguna → Tindakan yang disetujui
```

Keluaran integrasi yang ditargetkan adalah aplikasi yang terhubung dengan alur AI, disertai hasil pengujian. Backend mengendalikan hak akses, pemeriksaan isian, status pengajuan, dan penerapan perubahan yang sudah disetujui.

## Teknologi yang Direncanakan

| Bagian | Teknologi | Peran |
| --- | --- | --- |
| Antarmuka | React, Vite, Tailwind CSS | Formulir, unggahan, status, dan tampilan pemeriksaan |
| Backend | FastAPI | Logika layanan, hak akses, dan integrasi AI |
| Basis data | PostgreSQL melalui Supabase | Konfigurasi layanan, pengajuan, dan riwayat |
| Autentikasi dan penyimpanan | Supabase Auth dan Storage | Identitas pengguna dan penyimpanan dokumen privat |
| Orkestrasi AI | Langflow | Pemeriksaan awal, ringkasan, dan pemulihan revisi |
| Bantuan pengembangan | IBM Bob | Perencanaan, implementasi, perbaikan, dan pengujian kode |

Pemilihan model AI akan mempertimbangkan kemampuan analisis dokumen, dukungan bahasa, biaya, dan pengelolaan data. Detail rancangan tersedia dalam [arsitektur dan integrasi](docs/arsitektur.md).

## Dampak yang Diharapkan

| Indikator | Target Awal Pengujian |
| --- | --- |
| Waktu aktif mahasiswa menyelesaikan pengajuan | Penurunan median minimal 20% dibandingkan proses pembanding |
| Waktu aktif peninjau memeriksa pengajuan | Penurunan median minimal 20% tanpa menurunkan ketepatan keputusan |
| Keberhasilan memperbaiki revisi | Minimal 80% tugas revisi selesai dengan benar pada pengiriman ulang pertama |
| Pemahaman mahasiswa | Minimal 80% peserta dapat menjelaskan koreksi dan alasannya tanpa bantuan |
| Kesalahan saat pengajuan dikirim | Lebih sedikit dibandingkan proses pembanding |

**Angka tersebut merupakan target, bukan hasil yang sudah dicapai.** Evaluasi direncanakan menggunakan tugas yang setara, data sintetis, dan masukan calon pengguna. Rinciannya ada pada [rencana validasi](docs/validasi.md).

## Model Bisnis dan Potensi Pengembangan

FlowFix dirancang sebagai layanan berlangganan untuk institusi. Paket layanan dapat didasarkan pada jumlah layanan administrasi dan volume pengajuan, dengan biaya penyiapan awal untuk pemetaan SOP, konfigurasi, serta pelatihan bila diperlukan. Harga akan ditentukan setelah kebutuhan, biaya operasional, dan kesediaan membayar dipelajari.

Pengembangan dimulai dari alur magang lengkap dan satu layanan sederhana, yaitu surat keterangan mahasiswa aktif. Tahap berikutnya dapat mencakup cuti akademik, izin penelitian, dan layanan lain melalui konfigurasi yang sama.

Arsitektur multi-tenant direncanakan agar beberapa institusi memiliki pengguna, SOP, dan data masing-masing. Integrasi sistem informasi akademik, SSO, analitik, dan tanda tangan digital merupakan arah pengembangan lanjutan. Kapasitas layanan akan ditentukan melalui pengujian beban.

Baca [model bisnis](docs/bisnis.md) dan [arah pengembangan](docs/pengembangan.md).

## AI yang Bertanggung Jawab

FlowFix menempatkan persetujuan manusia sebagai bagian utama proses. Temuan AI perlu menyertakan bukti, informasi yang tidak pasti perlu ditandai, dan mahasiswa perlu memiliki jalur untuk meminta pemeriksaan manusia.

Rancangan juga mencakup pembatasan pengumpulan data, pemisahan akses antarinstansi dan pengguna, serta perlindungan terhadap instruksi berbahaya di dalam dokumen. Pengujian awal menggunakan data sintetis. Antarmuka direncanakan mudah dipahami, dapat diakses melalui ponsel, serta mendukung navigasi papan ketik dan pembaca layar.

Penjelasan lengkap tersedia pada [prinsip AI yang bertanggung jawab](docs/ai-bertanggung-jawab.md).

## Pitch Deck

Presentasi ide awal terdiri dari delapan slide berbahasa Indonesia:

- [PowerPoint yang dapat diedit](presentasi/FlowFix-Pitch-Deck.pptx)
- [PDF untuk pengumpulan](presentasi/FlowFix-Pitch-Deck.pdf)

## Dokumen Pendukung

- [Arsitektur dan integrasi teknologi](docs/arsitektur.md)
- [Rencana validasi masalah dan dampak](docs/validasi.md)
- [Model bisnis dan posisi solusi](docs/bisnis.md)
- [Prinsip AI yang bertanggung jawab](docs/ai-bertanggung-jawab.md)
- [Ruang lingkup dan arah pengembangan](docs/pengembangan.md)

## Prototipe Antarmuka dan Berkas Pengumpulan

[Prototipe antarmuka](prototipe/index.html) dapat dibuka langsung di peramban setelah repositori diunduh. Tampilan ini menjelaskan alur layanan, pemeriksaan awal, dan persetujuan koreksi menggunakan data simulasi. Langflow, IBM Bob, dan backend belum terhubung.

Lima [screenshot prototipe](submission/panduan-pengumpulan.md#3-project--prototype-screenshot) disiapkan untuk melengkapi pengajuan ide. Diagram integrasi ditandai sebagai rancangan, sementara hasil pemeriksaan dan koreksi ditandai sebagai simulasi.

![Prototipe layanan kampus FlowFix dengan data simulasi](submission/screenshot/01-layanan-kampus.png)

[Panduan pengumpulan](submission/panduan-pengumpulan.md) menjelaskan berkas untuk kolom tautan proyek, pitch deck, dan screenshot.
