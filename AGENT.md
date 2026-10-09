# AGENT.md — Catatan Sesi Pengembangan FlowFix dengan IBM Bob

> Dokumen ini merangkum seluruh pekerjaan yang dilakukan bersama IBM Bob (AI Agent)
> dalam membangun dan mendeploy sistem **FlowFix** — AI-Powered Student Administration
> & Failure Recovery Agent untuk Hackathon.

---

## Ringkasan Proyek

**FlowFix** adalah sistem administrasi pengajuan magang mahasiswa berbasis AI yang terdiri dari:

- **4 Langflow AI Flows** — analisis dokumen, asisten chat, ringkasan peninjau, perencana revisi
- **Laravel Backend** (PHP 8.2 + SQLite) — REST API lengkap dengan autentikasi Sanctum
- **React Frontend** (Vite + React 19) — UI mahasiswa dan peninjau
- **Local LLM** via 9Router (port 20128) — model `semua_model_dipilih`
- **Cloudflare Tunnel** — expose LLM local ke internet

---

## Stack Teknologi

| Komponen | Teknologi | Port |
|----------|-----------|------|
| Frontend | React 19 + Vite + TypeScript + Tailwind | 5173 |
| Backend | Laravel 12 + PHP 8.2 + SQLite | 8000 |
| AI Orchestration | Langflow 1.11.6 | 7860 |
| LLM Server | 9Router (OpenAI-compatible) | 20128 |
| Tunnel | Cloudflare Quick Tunnel | - |

---

## Kronologi Pekerjaan

### Fase 1 — Setup MCP & Koneksi Langflow (29 Sep 2026)

**Yang dilakukan:**
- Membangun custom **MCP server** (`langflow-mcp-server`) berbasis Node.js/TypeScript
- Mengekspos 10 Langflow REST API tools ke IBM Bob
- Mendaftarkan server di `.bob/mcp.json`
- Membuat folder **FLOWFIX** di Langflow (`3d3c3531-3b7b-4334-8299-e7f0b1969441`)

**Masalah yang ditemukan & diselesaikan:**
- `KeyError('data')` pada graph builder Langflow — edges harus punya `data.sourceHandle` nested dict
- `LanguageModelComponent` crash karena field `model` tidak kompatibel
- `lf_version` harus di-set manual ke `"1.11.6"` pada setiap node
- SSRF Protection Langflow memblokir IP local LAN — patch `ssrf_protection.py`

---

### Fase 2 — Rebuild 4 AI Flows (30 Sep 2026)

**Flows yang dibangun:**

| Flow | ID | Fungsi |
|------|----|--------|
| `submission-assistant` | `18f5878c-...` | Chat asisten pengajuan magang |
| `document-precheck` | `492d4197-...` | Pemeriksaan kelengkapan & konsistensi dokumen |
| `reviewer-summary` | `dbaa87ec-...` | Ringkasan pengajuan untuk peninjau |
| `revision-planner` | `fd6a11cd-...` | Rencana revisi berdasarkan komentar peninjau |

**Arsitektur tiap flow:**
```
ChatInput (ci-main) -> Prompt Template -> OpenAI LLM -> ChatOutput
```

**Root causes yang diselesaikan:**
- Multi-ChatInput menyebabkan Langflow `/run` API gagal — redesign ke single input
- LLM node menggunakan `ext:openai:OpenAIModelComponent@official` (ada `openai_api_base`)
- Prompt template harus punya field `input` yang ter-deklarasi eksplisit

**Hasil:** TC01 test PASS — `document-precheck` mengembalikan JSON valid `{"status":"PASS",...}`

---

### Fase 3 — Setup Backend Laravel (02 Oct 2026)

**Yang dilakukan:**
- Install **Composer 2.10.3** ke `C:\xampp\php\`
- Install 115 Composer dependencies
- Generate `APP_KEY`, buat SQLite database
- Jalankan 4 migrations (users, cache, jobs, flowfix_tables)
- Seed 2 demo users:
  - `mahasiswa@flowfix.test` / `FlowFix-demo-2026!` (role: student)
  - `peninjau@flowfix.test` / `FlowFix-demo-2026!` (role: reviewer)
- Fix `LangflowService.php` — mapping single-input pattern (`'input' => 'ci-main'`)
- Fix `flowfix.php` config mapping untuk semua 4 flows
- Update system prompt semua flows dengan instruksi output JSON ketat sesuai schema

**File konfigurasi penting:**
- `.env` — `LANGFLOW_API_KEY`, `LANGFLOW_*_ID` untuk 4 flows
- `config/flowfix.php` — mapping flow input ke komponen Langflow
- `config/cors.php` — allow `localhost:5173` dan `127.0.0.1:5173`

---

### Fase 4 — Setup Frontend React (02 Oct 2026)

**Yang dilakukan:**
- Install 86 npm packages (React 19, Vite 6, Tailwind CSS 4, Lucide React)
- Set `VITE_API_BASE_URL=http://localhost:8000/api`
- TypeScript check bersih (0 errors)

---

### Fase 5 — Bug Fixes Produksi (02 Oct 2026)

#### Fix 1: CORS Error
- **Problem:** Frontend `127.0.0.1:5173` diblokir CORS — backend hanya allow `localhost:5173`
- **Fix:** `config/cors.php` — allow kedua origin sekaligus

#### Fix 2: Ekstraksi PDF Gagal
- **Problem:** `FLOWFIX_PYTHON=python` tidak punya `pypdf`; `PdfReader(strict=True)` gagal untuk semua PDF test
- **Fix:** Ganti ke `C:\Users\kenry\langflow\.venv\Scripts\python.exe`; ubah `strict=False`

#### Fix 3: Lock Macet (`ACTION_IN_PROGRESS`)
- **Problem:** Request AI timeout -> PHP mati tanpa release file lock -> semua tombol tidak bisa diklik
- **Fix:** `SerializeSubmissionActions.php` — `forceRelease()` otomatis sebelum retry

#### Fix 4: Timeout 60 Detik
- **Problem:** `Maximum execution time of 60 seconds exceeded` saat precheck 5 dokumen
- **Fix:** Artisan serve dengan `-dmax_execution_time=0 -ddefault_socket_timeout=300`

#### Fix 5: `mbstring` Tidak Tersedia
- **Problem:** `-c uploads.ini` menggantikan `php.ini` sepenuhnya -> `mbstring` OFF -> `mb_strimwidth()` undefined -> artisan crash
- **Fix:** Ganti dari `-c` ke `-d` flags (hanya override setting tertentu, tidak ganti php.ini)

---

### Fase 6 — Cloudflare Tunnel (03 Oct 2026)

**Yang dilakukan:**
- Download `cloudflared 2024.12.2` untuk Windows x64
- Buat Quick Tunnel untuk expose LLM local (port 20128) ke internet
- URL aktif: `https://parliament-periodic-meter-administrator.trycloudflare.com`
- Update semua 4 Langflow flows dengan URL tunnel baru
- Buat script `update_tunnel_url.py` untuk memperbarui URL saat tunnel restart

---

## Cara Menjalankan (Start Demo)

### 1. Start semua services sekaligus:
```powershell
# Dari folder repo
.\start-demo.ps1
```

### 2. Atau manual per terminal:
```powershell
# Terminal 1 - Langflow
C:\Users\kenry\langflow\.venv\Scripts\python.exe -m langflow run --host 127.0.0.1 --port 7860

# Terminal 2 - Backend Laravel (HARUS pakai -d flags, BUKAN -c)
cd backend
C:\xampp\php\php.exe -dmax_execution_time=0 -ddefault_socket_timeout=300 artisan serve --host=127.0.0.1 --port=8000

# Terminal 3 - Frontend
cd frontend
npm run dev

# Terminal 4 - Cloudflare Tunnel (untuk akses luar jaringan)
cloudflared.exe tunnel --url http://localhost:20128
```

### 3. Setelah tunnel restart, update URL flows:
```powershell
python update_tunnel_url.py --auto
```

---

## Akses Aplikasi

| URL | Keterangan |
|-----|-----------|
| `http://localhost:5173` | Web app FlowFix |
| `http://localhost:8000` | Laravel API |
| `http://localhost:7860` | Langflow dashboard |

### Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Mahasiswa | `mahasiswa@flowfix.test` | `FlowFix-demo-2026!` |
| Peninjau | `peninjau@flowfix.test` | `FlowFix-demo-2026!` |

---

## Konfigurasi LLM

| Setting | Nilai |
|---------|-------|
| Endpoint local | `http://192.168.56.1:20128/v1` |
| Endpoint publik (tunnel) | `https://parliament-periodic-meter-administrator.trycloudflare.com/v1` |
| API Key | `sk-785c777abfc898a8-aa7nz1-faaf2f71` |
| Model | `semua_model_dipilih` |

---

## File Penting

```
Hackathon---FlowFix/
├── start-demo.ps1                          # Script startup semua services
├── AGENT.md                                # Dokumen ini
├── backend/
│   ├── .env                                # Konfigurasi lokal (tidak di-commit)
│   ├── app/Services/LangflowService.php    # Integrasi ke Langflow API
│   ├── app/Services/DocumentExtractionService.php
│   ├── app/Http/Middleware/SerializeSubmissionActions.php
│   ├── config/flowfix.php                  # Mapping flow & konfigurasi app
│   ├── config/cors.php                     # CORS whitelist
│   ├── scripts/extract_pdf.py              # Python PDF extractor (pypdf)
│   └── runtime/php/conf.d/uploads.ini      # Dokumentasi PHP settings
├── frontend/
│   ├── .env                                # VITE_API_BASE_URL (tidak di-commit)
│   └── src/                                # React components & services
└── langflow/
    └── application/                        # Export JSON semua 4 flows (clean, no credentials)

Bob-Hackathon/  (workspace IBM Bob)
├── cloudflared.exe                         # Cloudflare tunnel binary
├── update_tunnel_url.py                    # Script update URL tunnel ke flows
└── patch_flow_prompts.py                   # Script patch system prompt flows
```

---

## Commit History

| Tanggal | Commit | Deskripsi |
|---------|--------|-----------|
| 27 Sep | `82e1874` | Inisialisasi konsep FlowFix |
| 29 Sep | `7e8a33a` | Tambah 7 Langflow flows (arsitektur awal) |
| 30 Sep | `b890bc5` | Update langflow & test dokumen |
| 30 Sep | `389323f` | Test frontend & backend (belum lengkap) |
| 30 Sep | `fc0bce7` | Rebuild semua 4 flow agar bisa dijalankan via API |
| 02 Oct | `a85f3ba` | Koneksi backend+frontend ke Langflow flows |
| 02 Oct | `815a124` | Fix CORS: allow localhost dan 127.0.0.1 |
| 02 Oct | `6058427` | Fix PDF extractor: strict=False |
| 02 Oct | `1754827` | Fix file lock macet di middleware |
| 02 Oct | `acbe488` | Fix PHP timeout 300s untuk precheck 5 dokumen |
| 02 Oct | `59c12b7` | Fix -d flags (bukan -c) agar mbstring tetap aktif |

---

## Alur Aplikasi yang Sudah Berfungsi

```
Mahasiswa login
    |
    +--> Buat submission baru
          |
          +--> Chat dengan Submission Assistant AI
          |         (flow: submission-assistant)
          |
          +--> Upload 5 dokumen PDF wajib
          |     (CV, Transkrip, Surat Penerimaan, Persetujuan PA, Formulir)
          |
          +--> Jalankan Pemeriksaan Awal
          |         (flow: document-precheck)
          |
          +--> Kirim pengajuan
                |
                +--> Peninjau login
                      |
                      +--> Lihat Reviewer Summary AI
                      |         (flow: reviewer-summary)
                      |
                      +--> Approve / Request Revision
                            |
                            +--> [Jika revisi] Mahasiswa buat Revision Plan AI
                            |         (flow: revision-planner)
                            |
                            +--> Apply perubahan & kirim ulang
```

---

*Dibuat dengan IBM Bob AI Agent — Hackathon FlowFix 2026*
