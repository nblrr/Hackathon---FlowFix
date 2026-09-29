# FlowFix AI Flows

This folder contains seven Langflow flow files, each representing one function. **Document Pre-Check now supports PDF uploads on the local Langflow 1.12.1 instance.** Upload, extraction, and prompt construction have been tested. End-to-end AI verification is pending a valid Google/Gemini API key; the inference attempt returned `API_KEY_INVALID`. See the [PDF testing guide and sample files](examples/README.md).

Document Pre-Check uses Text Input for SOP and form data, a PDF upload component, Prompt Template, Agent, and Chat Output. The other flow descriptions below describe their earlier designs; they have not been reverified in this update. Responsibility boundaries follow the [FlowFix architecture](../dokumentasi/arsitektur-sistem.md).

## Core Flows

| File | Canonical Name | Function | Status |
| --- | --- | --- | --- |
| [submission-assistant.json](submission-assistant.json) | `Submission Assistant` | Primary conversational interface for students: detects intent, guides process, collects information progressively, requests documents, explains pre-check findings, answers status questions | **Priority — primary entry point** — `mcp_enabled: true` |
| [document-precheck.json](document-precheck.json) | `Document Pre-Check` | Checks document completeness and consistency against SOP. Inputs use per-document identity (filename + document_type + content) | **Priority** — `mcp_enabled: true`, ready to import |
| [reviewer-summary.json](reviewer-summary.json) | `Reviewer Summary` | Generates structured summary for the human reviewer | **Priority** — `mcp_enabled: true`, ready to import |
| [revision-planner.json](revision-planner.json) | `Revision Planner` | Proposes specific corrections from reviewer feedback and updated evidence | **Priority** — `mcp_enabled: true`, ready to import |

## Supporting Flows

| File | Canonical Name | Function | Status |
| --- | --- | --- | --- |
| [notification-composer.json](notification-composer.json) | `Notification Composer` | Composes notification text (does not send) | Future development — sending handled by backend |
| [audit-record-generator.json](audit-record-generator.json) | `Audit Record Generator` | Proposes audit trail entries (does not write to database) | Experimental — official state managed by backend |
| [submission-router.json](submission-router.json) | `Submission Router` | Proposes routing between flows (does not execute routing). Most routing should be deterministic application logic | Experimental — backend executes route based on JSON output |

## Submission Assistant — Conversational Entry Point

The Submission Assistant is the primary interface for students. It does not replace the three document flows — it orchestrates the student experience and translates Document Pre-Check findings into natural language.

**Inputs:**

| Input | Required | Description |
| --- | --- | --- |
| `submission_state` | Required | JSON object with current submission state from backend. Pass `{}` if no submission started. |
| `user_message` | Required | Student's natural-language message. |
| `sop_context` | Optional | SOP/requirements text for the current submission type. Must be supplied for requirement questions. |

**Supported intents:**

| Intent | Trigger |
| --- | --- |
| `START_SUBMISSION` | Student wants to start a new submission |
| `CHECK_REQUIREMENTS` | Student asks about required documents or process |
| `UPLOAD_DOCUMENT` | Student wants to upload a document |
| `CHECK_SUBMISSION` | Student asks about findings or completeness |
| `FIX_SUBMISSION` | Student wants to fix submission or understand revision |
| `CHECK_STATUS` | Student asks about submission status |
| `ASK_QUESTION` | General question about the process |

**Next action values (returned in structured output):**

`COLLECT_SUBMISSION_INFO` | `SHOW_REQUIREMENTS` | `REQUEST_DOCUMENT_UPLOAD` | `RUN_DOCUMENT_PRECHECK` | `SHOW_PRECHECK_RESULT` | `SHOW_STATUS` | `PLAN_REVISION` | `ANSWER_QUESTION` | `REQUEST_HUMAN_REVIEW`

**Structured output schema:** [`schemas/submission-assistant.schema.json`](schemas/submission-assistant.schema.json)

The UI shows only the `message` field. All other fields are for application orchestration.

## Document Identity Contract

Flows that process documents use a per-document JSON array, not a flat filename→text map:

```json
[
  {
    "filename": "Acceptance_Letter.pdf",
    "document_type": "acceptance_letter",
    "content": "extracted text..."
  }
]
```

Use `"content": "[UNREADABLE]"` for documents that could not be parsed. This format applies to `document-precheck.json`, `reviewer-summary.json`, and `revision-planner.json`.

## Input Contracts

| Flow | Input | Required |
| --- | --- | --- |
| submission-assistant | `submission_state`, `user_message` | Required; `sop_context` optional |
| document-precheck | `sop_requirements`, `form_data`, `document_contents` | All required |
| reviewer-summary | `submission_data`, `document_contents` | Required; `precheck_result` optional |
| revision-planner | `reviewer_comment`, `current_submission`, `sop_rules` | Required; `updated_evidence` optional |

## Output Schemas

JSON schemas for output validation are in `schemas/`:

| Schema | Flow |
| --- | --- |
| [`schemas/submission-assistant.schema.json`](schemas/submission-assistant.schema.json) | submission-assistant |
| [`schemas/document-precheck.schema.json`](schemas/document-precheck.schema.json) | document-precheck |
| [`schemas/reviewer-summary.schema.json`](schemas/reviewer-summary.schema.json) | reviewer-summary |
| [`schemas/revision-planner.schema.json`](schemas/revision-planner.schema.json) | revision-planner |

Consumer (backend or Bob) must validate LLM output against the schema before using it.

## Model Configuration

Document Pre-Check preserves the local Agent's `gemini-flash-latest` selection through Google Generative AI. Configure a valid Google/Gemini API key in that node. Credentials are removed from the repository export.

Earlier flow designs used `gemini-1.5-flash` via an OpenAI-compatible endpoint:
- `openai_api_base`: `https://generativelanguage.googleapis.com/v1beta/openai/`
- `api_key`: **empty in export** — must be configured in the Langflow instance after import

If `gemini-1.5-flash` is not available, select an active model through the Langflow UI after import.

## MCP

Four core flows have `mcp_enabled: true` and `endpoint_name` set in the exported JSON:

| Flow | endpoint_name |
| --- | --- |
| submission-assistant | `submission_assistant` |
| document-precheck | `check_submission` |
| reviewer-summary | `summarize_submission` |
| revision-planner | `plan_revision` |

**Manual steps required after import:**
1. Open Langflow UI and enter each flow.
2. Verify `mcp_enabled` toggle is active in flow settings.
3. Verify `endpoint_name` matches the table above.
4. Enable the flow as an MCP tool in the project settings.
5. Restart the MCP connection in Bob and verify the tool appears.

See [Langflow MCP documentation](https://docs.langflow.org/mcp-server) for details.

## Testing

Test fixtures are in `tests/cases.json` (11 cases: TC-01 through TC-11). Test runner: `tests/run_tests.py`.

| Test Range | Coverage |
| --- | --- |
| TC-01 to TC-05 | Document Pre-Check: pass, missing doc, period mismatch, unreadable, NIM mismatch |
| TC-06 | Revision Planner: period correction with new evidence |
| TC-07 | Reviewer Summary: normal submission with PASS pre-check |
| TC-08 to TC-11 | Submission Assistant: start submission, check requirements, status after pre-check, revision explanation |

Dry-run (no API):
```
python tests/run_tests.py --dry-run
```

With API (after flows are imported):
```
export LANGFLOW_URL=http://localhost:7860
export LANGFLOW_API_KEY=<your-api-key>
export FLOWFIX_ID_SUBMISSION_ASSISTANT=<flow-id>
export FLOWFIX_ID_CHECK_SUBMISSION=<flow-id>
export FLOWFIX_ID_SUMMARIZE_SUBMISSION=<flow-id>
export FLOWFIX_ID_PLAN_REVISION=<flow-id>
python tests/run_tests.py
```

## MCP Connection Status

The local Bob configuration (`lf-hackathon`) connects to a Langflow instance. **This proves the MCP connection, not FlowFix flow execution.**

FlowFix flows must be imported into that instance and MCP enabled per flow before they can be called as tools.

See [status and next steps](../dokumentasi/tindak-lanjut.md) for the implementation sequence.

## PDF Handling

The local Document Pre-Check demo now reads PDFs directly inside Langflow:

```text
Upload PDF → extract text and preserve filename → document_contents JSON
SOP Requirements + Form Data + document_contents → Pre-Check Prompt → Agent → Result
```

The Upload PDF component uses Langflow's file reader and emits `[{filename, document_type, content}]`. Empty extracted text becomes `[UNREADABLE]`. Its code is in [pdf_document_input.py](pdf_document_input.py). See [test instructions](examples/README.md).

For the planned application, backend PDF parsing remains an alternative pipeline:

```text
Student uploads PDF
→ Backend: read file, extract text (e.g. pdfplumber or similar)
→ Backend: build document_contents array [{filename, document_type, content}]
→ Backend: call Document Pre-Check flow with document_contents
```

The prompt instructs the model to return `NEEDS_HUMAN_CHECK` for `[UNREADABLE]`. This expected model behavior still needs verification after fixing the API key. Parser errors for corrupt or locked files are surfaced rather than suppressed.

OCR is not enabled in this demo. Scanned PDFs without extractable text need a separate OCR step before analysis.
