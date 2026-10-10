FlowFix Test Pack
=================

Berisi 24 kasus uji sintetis untuk menguji sistem FlowFix end-to-end.

PENTING: SOP di sini adalah SIMULASI (bukan SOP resmi kampus manapun).
Seluruh identitas, NIM, nama perusahaan, dan data adalah fiktif.

ISI FOLDER
----------
test_cases.json          - 24 kasus uji lengkap (form data + teks dokumen + expected output)
                           Dipakai langsung oleh backend untuk demo cases di frontend.
sop_simulasi.txt         - Teks SOP simulasi (dibaca backend saat precheck)
SOP_Simulasi_Magang.pdf  - SOP simulasi versi PDF (untuk referensi)
Panduan_Test_Case_FlowFix.pdf - Panduan lengkap setiap test case
results_template.csv     - Template pencatatan hasil pengujian manual
dokumen/                 - 28 PDF uji (dokumen sintetis untuk diunggah ke aplikasi)
payload_langflow/        - 19 payload JSON siap pakai untuk pengujian langsung ke Langflow API

CARA MENGGUNAKAN
----------------
Demo via aplikasi (cara termudah):
  1. Jalankan .\start-demo.ps1
  2. Login sebagai mahasiswa
  3. Klik "Buat Pengajuan Baru"
  4. Di bagian formulir, pilih kasus demo dari dropdown (TC01–TC24)
  5. Upload PDF dari folder dokumen/ sesuai petunjuk test_cases.json

Mulai dari TC01 (dokumen 01–05): kasus paling lengkap dan konsisten → hasilnya PASS.

Kasus khusus:
  - dokumen/25_Surat_Penerimaan_Terkunci.pdf : PDF terkunci, password: flowfix-demo
  - dokumen/26_Dokumen_Rusak.pdf             : PDF sengaja rusak (uji error handling)

HUBUNGAN DENGAN tests/cases.json
---------------------------------
FlowFix-Test-Pack/test_cases.json  = 24 kasus LENGKAP dengan PDF nyata, dipakai di frontend demo
tests/cases.json                   = 11 kasus RINGKAS dengan teks sintetis, dipakai tests/run_tests.py

Keduanya berbeda tujuan, bukan duplikat.
