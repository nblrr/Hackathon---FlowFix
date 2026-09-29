# Arsitektur Sistem dan Integrasi

Dokumen ini menjelaskan rancangan teknis FlowFix pada tahap ide awal. Susunan komponen dipilih untuk memisahkan logika administrasi, pengelolaan data, dan bantuan AI.

## Gambaran Sistem

FlowFix adalah antarmuka percakapan yang memandu mahasiswa melalui proses pengajuan administrasi. Titik masuk utama bagi mahasiswa adalah **Submission Assistant** — chatbot yang memahami kebutuhan mahasiswa, menjelaskan persyaratan, mengumpulkan informasi secara bertahap, meminta dokumen yang diperlukan, meneruskan hasil pemeriksaan, dan menjawab pertanyaan tentang status pengajuan.

Validasi dokumen PDF adalah bagian dari percakapan tersebut, bukan produk yang berdiri sendiri.

```text
                         ┌────────────────────┐
                         │       User         │
                         └─────────┬──────────┘
                                   │
                                   ▼
                         ┌────────────────────┐
                         │ Submission         │
                         │ Assistant          │
                         └─────────┬──────────┘
                                   │
                 ┌─────────────────┼─────────────────┐
                 │                 │                 │
                 ▼                 ▼                 ▼
          Requirements       Submission        Status /
             Guidance           Intake          Questions
                                   │
                                   ▼
                           Document Upload
                                   │
                                   ▼
                           Document Reader
                                   │
                                   ▼
                         Document Pre-Check
                                   │
                                   ▼
                         Structured Findings
                                   │
                                   ▼
                         Submission Assistant
                                   │
                                   ▼
                                User
```

Sisi peninjau:

```text
Submission
    ↓
Reviewer Summary
    ↓
Human Reviewer
    ↓
Reviewer Feedback
    ↓
Revision Planner
    ↓
Submission Assistant
    ↓
Student
```

## Komponen Sistem

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
| React, Vite, Tailwind CSS | Antarmuka percakapan, unggahan, status, pemeriksaan, dan persetujuan koreksi |
| FastAPI | Identitas dan hak akses, pemeriksaan isian, perubahan status, routing, serta pemanggilan alur AI |
| Supabase Auth | Autentikasi pengguna yang dipetakan ke institusi dan perannya |
| PostgreSQL | Penyimpanan konfigurasi layanan, state pengajuan, versi dokumen, keputusan, dan riwayat |
| Supabase Storage | Penyimpanan dokumen privat dengan pemeriksaan akses |
| Ekstraksi dokumen | Pengambilan teks dan referensi halaman, serta penandaan berkas yang tidak terbaca |
| Langflow | Pengaturan alur Submission Assistant, pemeriksaan dokumen, ringkasan, dan pemulihan revisi |
| Model AI | Pemahaman bahasa alami, interpretasi isi dokumen, penjelasan temuan, dan penalaran atas umpan balik peninjau |

## Submission Assistant

Submission Assistant adalah antarmuka percakapan utama bagi mahasiswa. Tanggung jawabnya:

- Memahami apa yang ingin dilakukan mahasiswa
- Menjelaskan proses yang diperlukan
- Menjelaskan persyaratan dokumen berdasarkan SOP yang tersedia
- Mengumpulkan informasi yang diperlukan secara bertahap
- Menentukan informasi apa yang belum tersedia dan memintanya satu per satu
- Meminta dokumen hanya setelah jenis pengajuan diketahui
- Meneruskan temuan pemeriksaan dokumen dalam bahasa yang dapat dipahami mahasiswa
- Menjawab pertanyaan tentang status pengajuan saat ini
- Menjelaskan umpan balik peninjau dan langkah revisi
- Mengarahkan mahasiswa ke langkah berikutnya

Submission Assistant tidak membuat keputusan administratif resmi.

### Deteksi Intent

Submission Assistant menentukan kebutuhan mahasiswa saat ini. Model intent yang digunakan sederhana dan hanya mencakup intent yang benar-benar berguna untuk FlowFix:

| Intent | Deskripsi |
| --- | --- |
| `START_SUBMISSION` | Mahasiswa ingin memulai pengajuan baru |
| `CHECK_REQUIREMENTS` | Mahasiswa bertanya tentang persyaratan dokumen atau proses |
| `UPLOAD_DOCUMENT` | Mahasiswa ingin mengunggah dokumen |
| `CHECK_SUBMISSION` | Mahasiswa bertanya tentang temuan atau kelengkapan pengajuan |
| `FIX_SUBMISSION` | Mahasiswa ingin memperbaiki pengajuan atau memahami revisi |
| `CHECK_STATUS` | Mahasiswa menanyakan status pengajuan saat ini |
| `ASK_QUESTION` | Pertanyaan umum tentang proses atau layanan |

Jika klasifikasi intent tidak membutuhkan pemanggilan LLM terpisah, deteksi intent dikembalikan sebagai bagian dari keluaran terstruktur model percakapan yang sama.

### Keluaran Terstruktur

Keputusan percakapan dibuat dalam bentuk terstruktur di dalam sistem. Antarmuka hanya menampilkan pesan alami kepada mahasiswa.

```json
{
  "intent": "START_SUBMISSION",
  "message": "Tentu. Saya dapat membantu Anda menyiapkan pengajuan magang.",
  "next_action": "COLLECT_SUBMISSION_INFO",
  "required_information": [
    "student_id",
    "company_name",
    "internship_period"
  ]
}
```

### Panduan Persyaratan

Submission Assistant menjawab pertanyaan persyaratan berdasarkan konteks SOP yang tersedia, bukan pengetahuan model semata. Jika aturan yang relevan tidak tersedia, asisten menyatakan tidak dapat mengkonfirmasi persyaratan tersebut daripada mengarang jawaban.

```text
Pertanyaan mahasiswa
     +
SOP / Persyaratan yang relevan
     +
State pengajuan saat ini
     ↓
Submission Assistant
     ↓
Jawaban
```

### Pengumpulan Informasi Bertahap

Asisten menentukan informasi apa yang sudah diketahui dan hanya meminta informasi yang masih kurang. Tidak menampilkan formulir besar sekaligus.

```text
Mahasiswa:
Saya ingin mengajukan magang.

FlowFix:
Tentu. Di perusahaan mana Anda akan magang?

Mahasiswa:
PT Contoh Indonesia.

FlowFix:
Berapa periode magang Anda?

Mahasiswa:
Januari sampai Maret 2027.

FlowFix:
Terima kasih. Sekarang silakan unggah Surat Penerimaan dan dokumen wajib lainnya.
```

## State Pengajuan

State pengajuan disimpan dalam bentuk terstruktur di backend, bukan dalam memori percakapan LLM.

```json
{
  "submission_type": "internship",
  "student_info": {},
  "submission_data": {},
  "required_documents": [],
  "uploaded_documents": [],
  "missing_documents": [],
  "precheck_result": null,
  "review_status": null,
  "reviewer_feedback": null
}
```

Riwayat percakapan membantu kelangsungan dialog. State terstruktur mencatat proses yang sebenarnya. Keduanya tidak boleh dicampur: LLM tidak boleh menjadi sumber kebenaran untuk status pengajuan.

## Alur AI dalam Langflow

| Flow | Nama Kanonik | Masukan | Proses | Keluaran |
| --- | --- | --- | --- | --- |
| Submission Assistant | `Submission Assistant` | State pengajuan, pertanyaan mahasiswa, SOP, dan temuan pemeriksaan | Memahami intent, memandu proses, menjelaskan temuan | Respons alami + struktur intent dan next_action |
| Pemeriksaan dokumen | `Document Pre-Check` | Isian formulir, teks dokumen, dan persyaratan | Mencari kekurangan dan ketidaksesuaian berdasarkan bukti | Status pemeriksaan, temuan, dan sumber bukti terstruktur |
| Ringkasan peninjau | `Reviewer Summary` | Pengajuan, dokumen, dan temuan pemeriksaan | Menyusun fakta penting dan bagian yang perlu diverifikasi | Ringkasan dengan referensi dokumen |
| Pemulihan revisi | `Revision Planner` | Komentar peninjau, bukti terbaru, aturan, dan versi pengajuan | Mengidentifikasi bagian terdampak dan mengusulkan koreksi | Alasan revisi, kolom terdampak, nilai lama, usulan nilai baru, dan sumber |

Flow pendukung:

| Flow | Nama Kanonik | Fungsi |
| --- | --- | --- |
| Pengarah alur | `Submission Router` | Mengusulkan routing antar-flow; eksekusi routing dilakukan backend |
| Audit status | `Audit Record Generator` | Menghasilkan proposal entri audit; penyimpanan dilakukan backend |
| Penyusun notifikasi | `Notification Composer` | Menyusun teks notifikasi; pengiriman dilakukan backend |

## Pemrosesan Dokumen

Ketika mahasiswa mengunggah PDF, alurnya adalah:

```text
Submission Assistant
        ↓
Document Upload
        ↓
Document Reader
        ↓
Parsed Content
        ↓
Document Pre-Check
        ↓
Structured Findings
        ↓
Submission Assistant
        ↓
Penjelasan kepada mahasiswa
```

LLM percakapan tidak membaca file PDF mentah. Parsing dokumen adalah langkah pemrosesan terpisah.

Temuan dari Document Pre-Check dikembalikan ke lapisan percakapan dalam bentuk terstruktur, lalu Submission Assistant menerjemahkannya menjadi penjelasan yang dapat dipahami mahasiswa.

## Batas Tindakan AI dan Logika Aplikasi

**LLM bertanggung jawab atas:**
- Memahami bahasa alami
- Menginterpretasikan isi dokumen
- Menjelaskan temuan
- Menalar umpan balik peninjau

**Logika aplikasi (backend) bertanggung jawab atas:**
- Kolom wajib dan validasi isian
- Pelacakan dokumen yang diunggah
- State dan routing
- Persistensi dan versi
- Hak akses dan izin
- Perubahan status pengajuan
- Penerapan koreksi setelah persetujuan mahasiswa

Backend memeriksa kolom wajib, jenis berkas, hak akses, dan perpindahan status melalui aturan aplikasi, bukan melalui inferensi model.

Usulan koreksi harus merujuk pada versi pengajuan dan bukti yang masih berlaku. Penerapannya memerlukan persetujuan mahasiswa. Persetujuan akhir hanya dapat diberikan peninjau yang berwenang.

Jika analisis gagal, bukti bertentangan, atau dokumen tidak terbaca, sistem menyediakan jalur pemeriksaan manusia.

## Hubungan IBM Bob dan Langflow

IBM Bob direncanakan membantu pengembang menulis kode, membangun penghubung API, dan menyiapkan pengujian. Langflow menjalankan analisis AI yang akan dipakai aplikasi. Keduanya dihubungkan melalui MCP pada lingkungan pengembangan.

1. Pengembang menyediakan kasus sintetis dan hasil yang diharapkan.
2. Alur Langflow disediakan sebagai alat MCP dengan nama dan fungsi yang jelas.
3. Bob memanggil alat tersebut dengan konteks pengujian yang sesuai.
4. Langflow mengembalikan hasil analisis untuk diperiksa.
5. Bob membantu membandingkan hasil dengan harapan serta menyiapkan perbaikan kode atau pengujian.
6. Pengembang meninjau perubahan dan menjalankan pengujian kembali.

[Langflow sebagai server MCP](https://docs.langflow.org/mcp-server), [penggunaan MCP pada IBM Bob](https://bob.ibm.com/docs/ide/configuration/mcp/mcp-in-bob).

## Pengelolaan Data

Kelompok data utama meliputi institusi dan pengguna, konfigurasi layanan dan SOP, pengajuan dan dokumen, state pengajuan dan riwayat percakapan, tugas pemeriksaan dan revisi, serta hasil analisis dan riwayat tindakan.

Rancangan multi-tenant memisahkan akses data berdasarkan institusi sekaligus kepemilikan atau penugasan pengguna. Pembatasan ini mencakup basis data, unduhan dokumen, konteks yang dikirim ke AI, dan hasil analisis.

Lihat [prinsip AI yang bertanggung jawab](prinsip-ai.md) untuk penjelasan privasi, pengawasan, dan penanganan ketidakpastian.
