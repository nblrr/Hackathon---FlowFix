# Langflow untuk aplikasi FlowFix

Hanya ada **empat flow aplikasi**, semuanya di folder [`application/`](application/). Impor ke Langflow hanya empat JSON berikut.

| Export yang digunakan | Nama setelah diimpor | Konfigurasi ID di `backend/.env` |
| --- | --- | --- |
| [submission-assistant.json](application/submission-assistant.json) | `FlowFix App - submission-assistant` | `LANGFLOW_SUBMISSION_ASSISTANT_ID` |
| [document-precheck.json](application/document-precheck.json) | `FlowFix App - document-precheck` | `LANGFLOW_DOCUMENT_PRECHECK_ID` |
| [reviewer-summary.json](application/reviewer-summary.json) | `FlowFix App - reviewer-summary` | `LANGFLOW_REVIEWER_SUMMARY_ID` |
| [revision-planner.json](application/revision-planner.json) | `FlowFix App - revision-planner` | `LANGFLOW_REVISION_PLANNER_ID` |

Export lama di tingkat atas dan tiga flow pendukung yang tidak digunakan sudah dihapus. Routing, pencatatan audit, dan tindakan aplikasi ditangani Laravel.

## Arti folder

- `application/`: empat flow yang dapat diimpor ke Langflow.
- `schemas/`: referensi format JSON keluaran flow; **bukan flow dan tidak perlu diimpor**. Validasi runtime aplikasi menggunakan [backend/resources/schemas/](../backend/resources/schemas/), termasuk usulan isian percakapan.
- `examples/`: PDF sintetis untuk uji ekstraksi. Paket utama adalah [FlowFix-Test-Pack](../FlowFix-Test-Pack/), yang memiliki 24 kasus dan SOP simulasi.

## Konfigurasi Gemini dan Langflow

1. Impor empat JSON dari `application/` ke Langflow. Jika sudah pernah diimpor, buka flow yang ada agar tidak membuat salinan tambahan.
2. Pada `document-precheck`, pilih komponen **Agent**, provider Google Generative AI, model Gemini yang tersedia, dan isi API key Gemini.
3. Pada tiga flow lainnya, konfigurasi provider/model Gemini dan API key pada komponen **Language Model**. Jika kolom API Key tersembunyi, buka parameter lanjutan. Simpan perubahan.
4. Isi `LANGFLOW_API_KEY` di `backend/.env` dengan **API key Langflow**, bukan key Gemini. Isi empat variabel ID sesuai flow hasil impor. Gunakan `LANGFLOW_BASE_URL=http://127.0.0.1:7860` untuk instance lokal.
5. Setelah mengubah konfigurasi Laravel, jalankan `php artisan config:clear` dari `backend/`.

Perubahan yang tersimpan di Langflow langsung berlaku untuk pemanggilan flow tersebut berikutnya. **Tidak perlu export ulang agar aplikasi menggunakan API key baru.** Export diperlukan hanya untuk memperbarui file JSON dalam repositori; file lokal tidak ikut berubah otomatis. Jangan memasukkan kredensial ke Git.

Menghapus export di repositori tidak menghapus flow yang sebelumnya sudah diimpor ke instance Langflow. Daftar flow di instance tersebut dikelola terpisah.

## Kontrak integrasi aplikasi

```text
React -> Laravel (PDF privat, ekstraksi, state, hak akses)
      -> Langflow (analisis teks) -> model Gemini yang dikonfigurasi
```

Laravel memanggil `POST /api/v1/run/{flow_id}` dengan input per komponen melalui `tweaks`. Pemetaan aktual ada di [backend/config/flowfix.php](../backend/config/flowfix.php). Jangan mengganti ID komponen pada export tanpa menyesuaikan pemetaan tersebut.

| Flow | Input |
| --- | --- |
| Submission Assistant | `submission_state`, `user_message`, `sop_context` |
| Document Pre-Check | `sop_requirements`, `form_data`, `document_contents` |
| Reviewer Summary | `submission_data`, `document_contents`, `precheck_result` |
| Revision Planner | `reviewer_comment`, `current_submission`, `updated_evidence`, `sop_rules` |

PDF diunggah ke Laravel, bukan ke node Langflow. [Ekstraktor lokal](../backend/scripts/extract_pdf.py) mempertahankan nomor halaman; Laravel menyusun dokumen beserta filename, document_type, content, versi, dan batas ekstraksi. Node `TextInput-Documents` menerima teks ini. OCR belum tersedia: scan tanpa teks memerlukan pemeriksaan manusia. Komponen upload PDF Langflow lama sudah tidak digunakan.

Pre-check export saat ini mengembalikan `status`, `summary`, dan `findings` berisi `type`, `message`, `field`, `document`, `evidence`. Laravel mengadaptasinya secara eksplisit ke temuan aplikasi, menambahkan pemeriksaan deterministik dan pemeriksaan manusia. Ini bukan schema validator lama dengan `severity`/`issue`.

Laravel memvalidasi keluaran sebelum digunakan. Saran AI tidak menyimpan koreksi, mengirim pengajuan, atau memberikan persetujuan otomatis.

## Status verifikasi

Endpoint versi instance lokal mengembalikan Langflow **1.12.1**. Pemeriksaan endpoint versi tidak membuktikan empat flow aplikasi berhasil dijalankan. Integrasi Gemini memerlukan API key, model, dan ID flow yang dikonfigurasi; keberhasilannya harus diuji tersendiri. Hasil palsu dalam tes terisolasi bukan bukti inferensi nyata.
