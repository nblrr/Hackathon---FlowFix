# Contoh PDF sintetis

PDF di folder ini dipertahankan sebagai contoh uji ekstraksi:

| PDF | Isi |
| --- | --- |
| [surat-magang-valid.pdf](surat-magang-valid.pdf) | Surat dengan data yang konsisten dengan formulir contoh lama |
| [surat-magang-nim-berbeda.pdf](surat-magang-nim-berbeda.pdf) | Surat dengan NIM berbeda dari formulir contoh lama |
| [dokumen-tanpa-teks.pdf](dokumen-tanpa-teks.pdf) | PDF tanpa teks yang dapat diekstrak |

Untuk demo aplikasi lengkap, gunakan manifest [FlowFix-Test-Pack/test_cases.json](../../FlowFix-Test-Pack/test_cases.json) dan PDF aslinya. Contoh lama ini tidak memenuhi seluruh lampiran wajib SOP paket 24 kasus; jangan mengharapkan PASS hanya dengan satu surat.

## Alur yang digunakan sekarang

Unggah PDF melalui aplikasi FlowFix. Laravel menyimpan berkas privat dan menjalankan [ekstraktor lokal](../../backend/scripts/extract_pdf.py). Teks dengan identitas dokumen dan nomor halaman diteruskan ke [flow pre-check aplikasi](../application/document-precheck.json).

Flow upload PDF langsung ke Langflow yang sebelumnya dipakai untuk eksperimen sudah dihapus dari repositori. Impor hanya empat flow dari `langflow/application/`; lihat [panduan konfigurasi](../README.md).

Ekstraksi teks tidak menjalankan OCR. PDF tanpa teks membutuhkan pemeriksaan manusia; berkas terkunci atau rusak ditandai gagal dan perlu diganti. Hasil ekstraksi tidak membuktikan hasil analisis Gemini.
