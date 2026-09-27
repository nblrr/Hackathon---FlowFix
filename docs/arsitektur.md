# Arsitektur dan Integrasi Teknologi

Dokumen ini menjelaskan rancangan teknis FlowFix pada tahap ide awal. Susunan komponen dipilih untuk memisahkan logika administrasi, pengelolaan data, dan bantuan AI.

## Gambaran Sistem

```text
Antarmuka mahasiswa, peninjau, dan administrator
                         |
                  Backend FastAPI
                         |
          ┌──────────────┼─────────────────┐
          |              |                 |
    Supabase Auth   PostgreSQL dan    Ekstraksi teks
                    Storage privat    dokumen
                                           |
                                        Langflow
                                           |
                                        Model AI
```

| Komponen | Tanggung Jawab yang Direncanakan |
| --- | --- |
| React, Vite, Tailwind CSS | Formulir dinamis, unggahan, status, pemeriksaan, dan persetujuan koreksi |
| FastAPI | Identitas dan hak akses, pemeriksaan isian, perubahan status, serta pemanggilan alur AI |
| Supabase Auth | Autentikasi pengguna yang dipetakan ke institusi dan perannya |
| PostgreSQL | Penyimpanan konfigurasi layanan, versi pengajuan, keputusan, dan riwayat |
| Supabase Storage | Penyimpanan dokumen privat dengan pemeriksaan akses |
| Ekstraksi dokumen | Pengambilan teks dan referensi halaman, serta penandaan berkas yang tidak terbaca |
| Langflow | Pengaturan alur pemeriksaan awal, ringkasan, dan pemulihan revisi |
| Model AI | Analisis teks dan hubungan antarinformasi berdasarkan konteks yang diberikan |

## Konfigurasi Layanan

Setiap layanan memiliki nama, kolom formulir, dokumen wajib, aturan pemeriksaan, referensi SOP, dan tahap persetujuan. Konfigurasi awal mendukung satu tahap peninjauan agar ruang lingkup tetap terarah.

Pengajuan magang dan surat keterangan mahasiswa aktif akan memakai mesin layanan serta struktur penyimpanan yang sama. Perubahan persyaratan disimpan sebagai versi baru agar aturan yang berlaku pada pengajuan lama tetap dapat ditelusuri.

## Alur AI dalam Langflow

| Alur | Masukan | Proses | Keluaran |
| --- | --- | --- | --- |
| Pemeriksaan awal | Isian formulir, teks dokumen, dan persyaratan | Mencari kekurangan informasi dan ketidaksesuaian berdasarkan bukti | Status pemeriksaan, temuan, dan sumber bukti |
| Ringkasan peninjau | Pengajuan, dokumen, dan temuan pemeriksaan | Menyusun fakta penting dan bagian yang memerlukan verifikasi | Ringkasan dengan referensi dokumen |
| Pemulihan revisi | Komentar peninjau, bukti terbaru, aturan, dan versi pengajuan | Mengidentifikasi bagian terdampak dan mengusulkan tindakan koreksi | Alasan revisi, kolom terdampak, nilai lama, usulan nilai baru, dan sumber |

Komponen yang direncanakan mencakup masukan konteks, templat instruksi, model bahasa, Agent, serta keluaran terstruktur. Agent akan memperoleh alat terbatas untuk membaca aturan dan bukti yang sudah diotorisasi backend. Nama komponen dan format pertukaran data disesuaikan dengan versi Langflow yang dipilih.

Keluaran pemeriksaan dibedakan menjadi **tidak ditemukan masalah**, **ditemukan kemungkinan masalah**, atau **perlu pemeriksaan manusia**. Hasil tanpa masalah hanya berlaku pada bukti dan aturan yang diperiksa; keaslian dokumen serta keputusan administratif tetap memerlukan manusia.

## Hubungan IBM Bob dan Langflow

IBM Bob direncanakan membantu pengembang menulis kode, membangun penghubung API, dan menyiapkan pengujian. Langflow menjalankan analisis AI yang akan dipakai aplikasi. Keduanya dihubungkan melalui MCP pada lingkungan pengembangan.

1. Pengembang menyediakan kasus sintetis dan hasil yang diharapkan.
2. Alur Langflow disediakan sebagai alat MCP dengan nama dan fungsi yang jelas.
3. Bob memanggil alat tersebut dengan konteks pengujian yang sesuai.
4. Langflow mengembalikan hasil analisis untuk diperiksa.
5. Bob membantu membandingkan hasil dengan harapan serta menyiapkan perbaikan kode atau pengujian.
6. Pengembang meninjau perubahan dan menjalankan pengujian kembali.

Hasil integrasi yang ditargetkan adalah penghubung aplikasi ke alur AI, keluaran yang dapat diproses antarmuka, serta catatan pengujian. Dalam penggunaan sehari-hari, backend FlowFix memanggil Langflow melalui API.

Dokumentasi Langflow mensyaratkan komponen Chat Output untuk menyediakan alur sebagai alat MCP. Karena aplikasi memerlukan keluaran terstruktur, hasil akan diformat untuk jalur MCP dan divalidasi kembali oleh penerimanya. Pengaturan autentikasi serta kecocokan versi akan diperiksa saat integrasi. [Langflow sebagai server MCP](https://docs.langflow.org/mcp-server), [penggunaan MCP pada IBM Bob](https://bob.ibm.com/docs/ide/configuration/mcp/mcp-in-bob).

## Batas Tindakan AI

Backend memeriksa kolom wajib, jenis berkas, hak akses, dan perpindahan status melalui aturan aplikasi. AI membantu memahami teks serta hubungan informasi.

Usulan koreksi harus merujuk pada versi pengajuan dan bukti yang masih berlaku. Penerapannya memerlukan persetujuan mahasiswa. Setelah perubahan, pengajuan diperiksa ulang dan dikirim kembali melalui tindakan mahasiswa. Persetujuan akhir hanya dapat diberikan peninjau yang berwenang.

Jika analisis gagal, bukti bertentangan, atau dokumen tidak terbaca, sistem menyediakan jalur pemeriksaan manusia. Dokumen resmi tidak diubah oleh AI.

## Pengelolaan Data

Kelompok data utama meliputi institusi dan pengguna, konfigurasi layanan dan SOP, pengajuan dan dokumen, tugas pemeriksaan dan revisi, serta hasil analisis dan riwayat tindakan.

Rancangan multi-tenant memisahkan akses data berdasarkan institusi sekaligus kepemilikan atau penugasan pengguna. Pembatasan ini mencakup basis data, unduhan dokumen, konteks yang dikirim ke AI, dan hasil analisis.

Lihat [prinsip AI yang bertanggung jawab](ai-bertanggung-jawab.md) untuk penjelasan privasi, pengawasan, dan penanganan ketidakpastian.
