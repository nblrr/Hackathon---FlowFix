# FlowFix — Langflow Flows

Folder ini berisi 7 flow Langflow yang mengimplementasikan arsitektur AI FlowFix sesuai [`docs/arsitektur.md`](../docs/arsitektur.md).

## Daftar Flow

| File | Nama Flow | Fungsi |
|---|---|---|
| `01-document-pre-check-validator.json` | Document Pre-Check & Validator | Memeriksa kelengkapan field wajib dan konsistensi data antar-dokumen sebelum pengajuan dikirim |
| `02-admin-summary-generator.json` | Admin Summary Generator | Membuat ringkasan terstruktur dari pengajuan untuk mempercepat review admin/dosen |
| `03-recovery-agent-revision-planner.json` | Recovery Agent & Revision Planner | Root cause analysis revisi, menghubungkan komentar peninjau dengan bukti, mengusulkan koreksi spesifik |
| `04-contextual-chatbot-state-aware.json` | Contextual Chatbot (State-Aware) | Chatbot yang mengetahui state pengajuan dan menjawab berdasarkan kondisi nyata mahasiswa |
| `05-audit-trail-state-manager.json` | Audit Trail & State Manager | Mencatat setiap event dan memperbarui state pengajuan sebagai source of truth |
| `06-student-failure-detection-agent.json` | Student Failure Detection Agent | Orkestrator utama: menerima trigger, menentukan routing (pre-check / review / recovery) |
| `07-notification-escalation-agent.json` | Notification & Escalation Agent | Menyusun notifikasi untuk mahasiswa dan admin, menangani eskalasi jika SLA terlampaui |

## Cara Import ke Langflow

1. Buka Langflow di `http://127.0.0.1:7860`
2. Klik **New Flow** → **Import**
3. Upload file `.json` yang diinginkan
4. Klik node **Gemini** di setiap flow → masukkan **Google API Key** di field `Google API Key`
5. Klik **Playground** untuk test

## Arsitektur Alur

```
Trigger (new_submission / revision_requested / resubmission)
        ↓
[06] Student Failure Detection Agent  ← Orkestrator, menentukan routing
        ↓                    ↓
[01] Document Pre-Check   [03] Recovery Agent
        ↓                    ↓
[02] Admin Summary        Mahasiswa review + setuju koreksi
        ↓                    ↓
Admin/Dosen Review        Resubmit → kembali ke Pre-Check
        ↓
[07] Notification Agent  ← dipanggil di setiap transisi state
        ↓
[05] Audit Trail         ← mencatat semua event
        ↓
[04] Contextual Chatbot  ← menjawab pertanyaan mahasiswa kapan saja
```

## Input/Output Setiap Flow

### 01 · Document Pre-Check & Validator
- **Input:** `sop_requirements` · `form_data` · `document_texts`
- **Output JSON:** `{ status, findings[], human_checks[], documents_checked }`
- **Status:** `PASS` | `NEEDS_FIX` | `NEEDS_HUMAN_CHECK`

### 02 · Admin Summary Generator
- **Input:** `submission_data` · `document_texts` · `precheck_result`
- **Output JSON:** `{ student, company, period, documents, key_facts[], human_verification_required[] }`

### 03 · Recovery Agent & Revision Planner
- **Input:** `reviewer_comment` · `current_submission` · `updated_evidence` · `sop_rules`
- **Output JSON:** `{ root_cause, affected_fields[{ current_value, proposed_value, evidence }], correction_steps[] }`

### 04 · Contextual Chatbot (State-Aware)
- **Input:** `submission_state` · `student_question`
- **Output:** Jawaban teks kontekstual berdasarkan state pengajuan

### 05 · Audit Trail & State Manager
- **Input:** `previous_state` · `new_event` · `action_data`
- **Output JSON:** `{ updated_state, audit_entry }`

### 06 · Student Failure Detection Agent
- **Input:** `trigger_type` · `submission_context` · `action_history`
- **Output JSON:** `{ routing_decision, detected_risks[], recommended_next_flow }`

### 07 · Notification & Escalation Agent
- **Input:** `trigger_event` · `submission_data` · `recipient_type` · `is_escalation`
- **Output JSON:** `{ notification_type, recipient, message, next_action, escalation }`

## Catatan

- Semua flow menggunakan **Google Gemini 2.0 Flash** via OpenAI-compatible API (`generativelanguage.googleapis.com/v1beta/openai/`)
- API Key harus diisi manual di setiap node Gemini setelah import (tidak disimpan di file JSON demi keamanan)
- Flow dibangun menggunakan IBM Bob + Langflow MCP integration
