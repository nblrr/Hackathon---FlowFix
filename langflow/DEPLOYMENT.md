# FlowFix — Langflow Deployment Guide

Dokumen ini menjelaskan cara deploy ulang 4 flow FlowFix ke instance Langflow manapun.

---

## Prasyarat

- Python 3.9+
- `pip install requests`
- Langflow 1.11.x berjalan di `http://127.0.0.1:7860`
- Local LLM server OpenAI-compatible (contoh: 9Router, LM Studio, Ollama)

---

## Struktur Folder

```
langflow/
├── application/               # Flow JSON (sudah di-export dari Langflow)
│   ├── document-precheck.json
│   ├── reviewer-summary.json
│   ├── revision-planner.json
│   └── submission-assistant.json
├── schemas/                   # JSON Schema output tiap flow
├── build_flows.py             # Script deploy ulang ke Langflow
├── test_tc01.py               # Test TC01 lengkap (TC01.json dari Test Pack)
├── test_all_flows.py          # Smoke test semua 4 flow
└── DEPLOYMENT.md              # File ini
```

---

## Deploy Flows ke Langflow

### 1. Set environment variables

```bash
export LANGFLOW_URL=http://127.0.0.1:7860
export LANGFLOW_API_KEY=<langflow-api-key>
export LANGFLOW_FOLDER_ID=<folder-id-di-langflow>
export LLM_BASE_URL=http://<LAN-IP-kamu>:20128/v1   # JANGAN pakai localhost
export LLM_API_KEY=<api-key-llm-server>
export LLM_MODEL=semua_model_dipilih
```

> **Penting:** Gunakan LAN IP (cek dengan `ipconfig`), bukan `localhost` atau `127.0.0.1`.
> Langflow punya SSRF protection yang memblokir loopback dan private IP secara default.
> Solusi: tambahkan IP ke `LANGFLOW_SSRF_ALLOWED_HOSTS`, atau patch
> `get_allowed_hosts()` di `lfx/utils/ssrf_protection.py`.

### 2. Jalankan script

```bash
python langflow/build_flows.py
```

Output yang diharapkan:
```
OpenAIModelComponent found in registry OK
--- Building: FlowFix App - document-precheck ---
  Uploaded: FlowFix App - document-precheck -> ID=...
...
=== ALL FLOWS UPLOADED ===
=== TESTING TC01 on document-precheck ===
SUCCESS! AI Response: {"status":"PASS","findings":[],"human_checks":[...]}
```

---

## Flow Descriptions

### 1. `document-precheck`
- **Input:** Teks gabungan SOP + data formulir + teks OCR dokumen
- **Output:** `{"status": "PASS"|"NEEDS_FIX"|"NEEDS_HUMAN_CHECK", "findings": [...], "human_checks": [...]}`
- **Trigger:** Saat mahasiswa upload dokumen pertama kali

### 2. `reviewer-summary`
- **Input:** Data pengajuan + teks dokumen + hasil pre-check
- **Output:** `{"student": {...}, "company": {...}, "period": {...}, "documents": [...], "key_facts": [...], "human_verification_required": [...]}`
- **Trigger:** Saat admin/dosen membuka halaman review pengajuan

### 3. `revision-planner`
- **Input:** Komentar reviewer + pengajuan saat ini + bukti baru + aturan SOP
- **Output:** `{"root_cause": "...", "affected_fields": [...], "correction_steps": [...]}`
- **Trigger:** Setelah admin klik "Request Revision"

### 4. `submission-assistant`
- **Input:** State pengajuan + pertanyaan mahasiswa
- **Output:** Jawaban teks dalam bahasa Indonesia yang kontekstual dan actionable
- **Trigger:** Chat bot mahasiswa di portal

---

## Test

```bash
# Test TC01 (full payload dari FlowFix-Test-Pack)
python langflow/test_tc01.py

# Smoke test semua 4 flow
python langflow/test_all_flows.py
```

---

## Catatan Teknis (Root Cause Issues yang Diperbaiki)

| Issue | Cause | Fix |
|-------|-------|-----|
| `KeyError('data')` saat graph build | Edges tidak punya `"data": {"sourceHandle": {...}, "targetHandle": {...}}` | Tambahkan `data` dict ke setiap edge |
| Flow tidak bisa dijalankan via `/run` | `LanguageModelComponent.model` punya `external_options.fields["data"]` yang crash graph builder | Ganti ke `ext:openai:OpenAIModelComponent@official` |
| Node tidak diproses runtime | `lf_version` tidak ada di node template | Set `lf_version: "1.11.6"` di setiap node |
| SSRF Protection error | Langflow memblokir `localhost` dan private IP ranges | Gunakan LAN IP di `LLM_BASE_URL`, atau allowlist di `LANGFLOW_SSRF_ALLOWED_HOSTS` |
| Multi-ChatInput crash | `/run` API Langflow tidak support lebih dari 1 ChatInput | Redesign ke single ChatInput yang menerima combined input |
