# Arsitektur Sistem dan Integrasi

Dokumen ini menjelaskan rancangan teknis FlowFix. Susunan komponen dipilih untuk memisahkan logika administrasi, pengelolaan data, dan bantuan AI.

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
              (React 19 + Vite + TypeScript + Tailwind)
                         |
                   Backend Laravel 12
                   (PHP 8.2 + SQLite)
                         |
           ┌─────────────┴─────────────┐
           |                           |
     Ekstraksi teks PDF           Langflow 1.12
     (pypdf via subprocess)            |
                                  LiteLLM Proxy
                                       |
                                  LLM Server (9Router)
```

| Komponen | Tanggung Jawab |
| --- | --- |
| React 19 + Vite + TypeScript + Tailwind | Antarmuka percakapan, unggahan, status, pemeriksaan, dan persetujuan koreksi |
| Laravel 12 + PHP 8.2 | Autentikasi Sanctum, REST API, serialisasi aksi, validasi output AI, pemanggilan flows |
| SQLite | State pengajuan, versi dokumen, keputusan, riwayat, dan audit events |
| pypdf (subprocess Python) | Ekstraksi teks PDF; penandaan halaman tidak terbaca atau dokumen terkunci/rusak |
| Langflow 1.12 | Orkestrasi 4 AI flows: Submission Assistant, Document Pre-Check, Reviewer Summary, Revision Planner |
| LiteLLMProxyComponent | Proxy ke LLM server dengan free-text model name (tidak terikat dropdown OpenAI) |
| LLM Server (9Router port 20128) | Inferensi bahasa alami lokal, model `hacka` |

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

Semua 4 flows menggunakan arsitektur single-input: `ChatInput (ci-main) → Prompt Template → LiteLLMProxyComponent → ChatOutput`. Input dikombinasikan menjadi satu JSON string oleh backend sebelum dikirim.

| Flow | Masukan | Keluaran |
| --- | --- | --- |
| `submission-assistant` | State pengajuan + pertanyaan mahasiswa | JSON: intent, message, next_action |
| `document-precheck` | SOP + form data + teks dokumen | JSON: status, findings[], summary |
| `reviewer-summary` | Data pengajuan + dokumen + hasil precheck | JSON: key_facts[], review_summary |
| `revision-planner` | Komentar peninjau + bukti + SOP | JSON: root_cause, affected_fields[], recommended_changes[] |

Output setiap flow divalidasi di backend menggunakan JSON Schema di `backend/resources/schemas/`.

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

IBM Bob digunakan untuk membangun seluruh sistem: menulis kode backend dan frontend, membangun flows Langflow via MCP, menyiapkan pengujian, dan debugging. Langflow menjalankan analisis AI yang dipakai aplikasi. Keduanya dihubungkan melalui MCP server `lf-hackathon` pada lingkungan pengembangan.

[Langflow sebagai server MCP](https://docs.langflow.org/mcp-server), [penggunaan MCP pada IBM Bob](https://bob.ibm.com/docs/ide/configuration/mcp/mcp-in-bob).

## Pengelolaan Data

Kelompok data utama meliputi institusi dan pengguna, konfigurasi layanan dan SOP, pengajuan dan dokumen, state pengajuan dan riwayat percakapan, tugas pemeriksaan dan revisi, serta hasil analisis dan riwayat tindakan.

Rancangan multi-tenant memisahkan akses data berdasarkan institusi sekaligus kepemilikan atau penugasan pengguna. Pembatasan ini mencakup basis data, unduhan dokumen, konteks yang dikirim ke AI, dan hasil analisis.

Lihat [prinsip AI yang bertanggung jawab](prinsip-ai.md) untuk penjelasan privasi, pengawasan, dan penanganan ketidakpastian.
