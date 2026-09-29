# Tes upload PDF di Langflow

Buka flow **FlowFix - Document Pre-Check**, lalu refresh halaman setelah perubahan melalui API.

1. Node **SOP Requirements** dan **Form Data (JSON)** sudah berisi data uji sintetis.
2. Pada node **Upload PDF**, pilih file melalui kolom **Files**. Hapus pilihan file sebelumnya bila ingin menguji satu dokumen saja.
3. Isi **Jenis Dokumen** sesuai SOP; contoh ini memakai `surat_penerimaan_magang`.
4. Konfigurasi kredensial melalui **Settings → Model Providers → Google Generative AI → API Key**, lalu simpan. API Key pada parameter lanjutan Agent dikosongkan agar memakai pengaturan provider ini.
5. Jalankan flow sampai **Pre-Check Result Output**.

| PDF | Tujuan | Hasil AI yang diharapkan |
| --- | --- | --- |
| [surat-magang-valid.pdf](surat-magang-valid.pdf) | Semua data cocok dengan formulir contoh | `PASS` |
| [surat-magang-nim-berbeda.pdf](surat-magang-nim-berbeda.pdf) | NIM surat `87654321`, formulir `12345678` | `NEEDS_FIX` dengan bukti perbedaan |
| [dokumen-tanpa-teks.pdf](dokumen-tanpa-teks.pdf) | PDF kosong tanpa teks yang dapat diekstrak | `NEEDS_HUMAN_CHECK` |

Semua PDF di folder ini merupakan berkas PDF sungguhan dengan data sintetis, bukan dokumen resmi.

## Status verifikasi

Pada 30 September 2026, upload melalui API lokal Langflow 1.12.1, ekstraksi PDF, dan pembuatan prompt berhasil diuji. Tes mencakup teks yang cocok, NIM berbeda, PDF tanpa teks, dan dua PDF dengan identitas file terpisah. PDF kosong menjadi `[UNREADABLE]`.

Eksekusi sampai Agent telah dicoba, tetapi Google menolak kredensial tersimpan dengan `API_KEY_INVALID`. Referensi API key tersembunyi `OPENAI_API` pada Agent sudah dihapus, lalu pengujian menggunakan pengaturan provider diulang dan masih menghasilkan `API_KEY_INVALID`. Kredensial Google pada pengaturan provider perlu diperbaiki. Status AI pada tabel merupakan hasil yang diharapkan, belum hasil inferensi yang terverifikasi.

## Dokumen sendiri

Sesuaikan SOP dan Form Data dengan kasus sebenarnya, lalu ganti PDF pada Upload PDF. Isi dokumen dan nama file akan dibuat menjadi JSON secara otomatis; tidak perlu menyalin teks PDF. Langflow dapat menambahkan waktu upload di depan nama file.

Semua PDF pada satu node memakai Jenis Dokumen yang sama. Untuk jenis dokumen berbeda, diperlukan pemetaan jenis per file atau node pembaca terpisah. Pembaca saat ini menggunakan ekstraksi teks biasa tanpa OCR. PDF scan yang tidak menghasilkan teks ditandai `[UNREADABLE]`; PDF rusak atau terkunci dapat menghentikan pembacaan dengan error.

Untuk impor ke instance lain, gunakan `../document-precheck.json`, konfigurasi model dan API key di Agent, lalu pilih PDF. Ekspor tidak menyertakan kredensial atau lokasi file lokal. Kode pembaca ada di `../pdf_document_input.py` dan sudah disematkan di ekspor JSON.
