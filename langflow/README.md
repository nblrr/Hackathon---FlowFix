# Langflow — FlowFix

Ada **empat flow aplikasi**, semuanya di folder [`application/`](application/). Impor ke Langflow hanya empat JSON ini.

| File | Nama flow | Variabel di `backend/.env` |
| --- | --- | --- |
| [submission-assistant.json](application/submission-assistant.json) | `FlowFix App - submission-assistant` | `LANGFLOW_SUBMISSION_ASSISTANT_ID` |
| [document-precheck.json](application/document-precheck.json) | `FlowFix App - document-precheck` | `LANGFLOW_DOCUMENT_PRECHECK_ID` |
| [reviewer-summary.json](application/reviewer-summary.json) | `FlowFix App - reviewer-summary` | `LANGFLOW_REVIEWER_SUMMARY_ID` |
| [revision-planner.json](application/revision-planner.json) | `FlowFix App - revision-planner` | `LANGFLOW_REVISION_PLANNER_ID` |

## Isi folder

- `application/` — 4 flow JSON siap impor. File ini **tanpa BOM**, langsung bisa diupload ke Langflow UI.
- `schemas/` — referensi format JSON keluaran tiap flow. Bukan flow, tidak perlu diimpor. Validasi runtime ada di `backend/resources/schemas/`.
- `examples/` — PDF sintetis untuk menguji ekstraksi dokumen.
- `build_flows.py` — script untuk rebuild semua 4 flow dari awal via Langflow API (dipakai jika flow perlu dibuat ulang).
- `DEPLOYMENT.md` — panduan lengkap deploy ulang ke instance Langflow baru.

## Setup (9Router / LLM lokal)

### Prasyarat

- Langflow 1.12+ berjalan di `http://127.0.0.1:7860`
- LLM server OpenAI-compatible (9Router, LM Studio, Ollama, dll) di port 20128
- **SSRF harus diizinkan** untuk LAN IP server LLM — jalankan Langflow dengan:

```powershell
$env:LANGFLOW_SSRF_ALLOWED_HOSTS = "192.168.x.x"   # ganti dengan LAN IP kamu
C:\Users\asus\langflow\.venv\Scripts\python.exe -m langflow run --host 127.0.0.1 --port 7860
```

### Upload flows

**Opsi A — Upload JSON manual (paling cepat):**
1. Buka `http://127.0.0.1:7860`
2. Klik ikon upload (↑) di sebelah Projects
3. Upload keempat file dari `langflow/application/`

**Opsi B — Rebuild via script:**
```powershell
$env:LANGFLOW_API_KEY  = "<api-key-langflow>"
$env:LLM_BASE_URL      = "http://<LAN-IP>:20128/v1"
$env:LLM_API_KEY       = "<api-key-llm-server>"
$env:LLM_MODEL         = "hacka"   # atau model lain yang tersedia
python langflow/build_flows.py
```

### Update `backend/.env`

Setelah flow berhasil diupload, isi variabel berikut di `backend/.env`:

```env
LANGFLOW_API_KEY=<api-key-dari-langflow-ui>
LANGFLOW_SUBMISSION_ASSISTANT_ID=<id-flow>
LANGFLOW_DOCUMENT_PRECHECK_ID=<id-flow>
LANGFLOW_REVIEWER_SUMMARY_ID=<id-flow>
LANGFLOW_REVISION_PLANNER_ID=<id-flow>
```

Setelah mengubah `.env`, jalankan `php artisan config:clear` dari folder `backend/`.

## Kontrak integrasi

```
React → Laravel (PDF, ekstraksi, state, auth)
      → Langflow (analisis teks) → LLM
```

Laravel memanggil `POST /api/v1/run/{flow_id}` dengan input via `tweaks`. Pemetaan komponen ada di `backend/config/flowfix.php`.

| Flow | Input yang dikirim |
| --- | --- |
| submission-assistant | `submission_state`, `user_message`, `sop_context` |
| document-precheck | `sop_requirements`, `form_data`, `document_contents` |
| reviewer-summary | `submission_data`, `document_contents`, `precheck_result` |
| revision-planner | `reviewer_comment`, `current_submission`, `updated_evidence`, `sop_rules` |

PDF diunggah ke Laravel, diekstrak oleh `backend/scripts/extract_pdf.py`, lalu teksnya dikirim ke Langflow. **Bukan** diupload langsung ke node Langflow.

Laravel memvalidasi semua output AI terhadap schema sebelum digunakan. Saran AI tidak menyimpan koreksi atau memberikan persetujuan secara otomatis — keputusan tetap di tangan manusia.
