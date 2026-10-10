# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project: FlowFix
AI-powered student internship-application system. Stack: Laravel 12 + PHP 8.2 + SQLite (backend), React 19 + Vite 6 + TypeScript 5.8 (frontend), Langflow 1.12 (AI orchestration), LiteLLM local LLM at port 20128.

## Critical startup
```powershell
.\start-demo.ps1          # starts all services in background windows
```
Backend MUST use `-d` flags (not `-c`) to avoid replacing `php.ini` and losing `mbstring`:
```powershell
cd backend
C:\xampp\php\php.exe -dmax_execution_time=0 -ddefault_socket_timeout=300 artisan serve --host=127.0.0.1 --port=8000
```
Langflow MUST start with `LANGFLOW_SSRF_ALLOWED_HOSTS=<LAN_IP>` set in the environment, or Langflow will block LLM calls to any local IP. Set `FLOWFIX_LLM_HOST` in `.env` at the repo root — `start-demo.ps1` reads it automatically.

## Commands

### Backend (run from `backend/`)
```powershell
composer setup         # install + migrate + seed (first-time)
composer test          # artisan config:clear + phpunit all tests
php artisan test --filter TestClassName      # single test class
php artisan test tests/Feature/SomeTest.php  # single file
vendor\bin\pint        # Laravel Pint code-style fixer
```

### Frontend (run from `frontend/`)
```powershell
npm run dev            # Vite dev server on 127.0.0.1:5173
npm run build          # tsc -b + vite build
npm run test:e2e       # Playwright end-to-end
```

### Integration / flow tests (from repo root)
```powershell
python tests/run_tests.py                    # all cases
python tests/run_tests.py --case TC-01       # single case
python tests/run_tests.py --dry-run          # validate inputs without calling API
```
Env vars required: `LANGFLOW_URL`, `LANGFLOW_API_KEY`, and per-flow `FLOWFIX_ID_CHECK_SUBMISSION` / `FLOWFIX_ID_SUMMARIZE_SUBMISSION` / `FLOWFIX_ID_PLAN_REVISION`.

### Rebuild and re-upload all 4 Langflow flows
```powershell
python langflow/build_flows.py    # requires LANGFLOW_API_KEY, LLM_BASE_URL, etc. as env vars
```

## Architecture: all 4 flows are single-input
Every Langflow flow uses **one** ChatInput node with id `"ci-main"`. Laravel's `LangflowService` JSON-encodes all input fields into a single string and passes it as `tweaks: {"ci-main": {"input_value": <combined_json>}}`. Multi-ChatInput causes the Langflow `/run` API to fail.

## AI output validation
`AiOutput::parse()` in `backend/app/Data/AiOutput.php` strips markdown fences, extracts the first JSON block, then validates against a JSON Schema file at `backend/resources/schemas/<flow-name>.schema.json`. Any schema mismatch throws `INVALID_AI_RESPONSE`. All 4 schema files must stay in sync with both the Langflow system prompts and the TypeScript types in `frontend/src/types.ts`.

## Langflow flow JSON format (gotchas)
- Edges must have **both** a top-level string `sourceHandle`/`targetHandle` (for UI) **and** a nested `data.sourceHandle`/`data.targetHandle` dict — the graph builder reads `e["data"]["sourceHandle"]["id"]` at runtime.
- Use `LiteLLMProxyComponent` (not `OpenAIModelComponent` / `LanguageModelComponent`) — the latter has `external_options.fields['data']` that causes `KeyError('data')`.
- Set `load_from_db=False` explicitly on `api_key` in `LiteLLMProxyComponent`.
- Each node template needs `lf_version: "1.11.6"` set manually.
- Export flow JSON via `build_flows.py`, not PowerShell `ConvertTo-Json` — PowerShell adds a UTF-8 BOM that causes 422 errors on upload.
- `folder_id` in upload requests must be a valid UUID (empty string is rejected by newer Langflow).

## Backend conventions
- All API errors use `ApiException(string $errorCode, string $message, int $status)` — `$errorCode` is a SCREAMING_SNAKE constant surfaced to the frontend.
- `SerializeSubmissionActions` middleware uses file-based cache locks (not DB locks) to serialize per-submission writes. `forceRelease()` is called before the second lock attempt — this is intentional to recover from PHP process kills (do not simplify away).
- PDF extraction: Laravel calls `backend/scripts/extract_pdf.py` as a subprocess via `FLOWFIX_PYTHON`. The Python executable **must** have `pypdf` installed (system `python` likely does not). Set `FLOWFIX_PYTHON` in `.env`. `PdfReader` is opened with `strict=False`.
- SOP file is read from `../FlowFix-Test-Pack/sop_simulasi.txt` (relative to `backend/`), configured in `config/flowfix.php`.

## Frontend conventions
- Auth token stored in `sessionStorage` under key `flowfix_token` (not localStorage).
- All API responses unwrap `data.data`; errors unwrap `data.error.{code, message, fields}`.
- File uploads use raw `XMLHttpRequest` for progress events; all other calls use `fetch` with a 125-second client timeout.
- TypeScript: `strict`, `noUnusedLocals`, `noUnusedParameters`, `verbatimModuleSyntax` all enabled.
- All types are in `frontend/src/types.ts` as compact one-liner type aliases (not interfaces).

## .env must-haves (backend)
`LANGFLOW_API_KEY`, `LANGFLOW_SUBMISSION_ASSISTANT_ID`, `LANGFLOW_DOCUMENT_PRECHECK_ID`, `LANGFLOW_REVIEWER_SUMMARY_ID`, `LANGFLOW_REVISION_PLANNER_ID`. Without these, `LangflowService::run()` throws `AI_NOT_CONFIGURED` (503) — this is a user-visible expected condition, not a crash.

## Demo accounts
| Role | Email | Password |
|------|-------|----------|
| Student | `mahasiswa@flowfix.test` | `FlowFix-demo-2026!` |
| Reviewer | `peninjau@flowfix.test` | `FlowFix-demo-2026!` |
