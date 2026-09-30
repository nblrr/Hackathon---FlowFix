"""
Build and upload all 4 FlowFix flows using proper Langflow component templates.
Each flow uses: 1x ChatInput -> Prompt Template -> ext:openai:OpenAIModelComponent@official -> ChatOutput

Key discoveries during development:
- LanguageModelComponent.model field has external_options.fields['data'] which causes
  KeyError('data') in Langflow's graph builder — use OpenAIModelComponent instead.
- Edges MUST have a 'data' dict with parsed sourceHandle/targetHandle (not just JSON strings),
  because Langflow's _get_edges_as_list_of_tuples() reads e["data"]["sourceHandle"]["id"].
- lf_version must be set on each node template for the graph builder to process them.
- Langflow SSRF protection blocks localhost/private IPs — use the machine's LAN IP,
  or add it to LANGFLOW_SSRF_ALLOWED_HOSTS env var.

Usage:
    pip install requests
    LANGFLOW_URL=http://127.0.0.1:7860 \\
    LANGFLOW_API_KEY=<your-key> \\
    LANGFLOW_FOLDER_ID=<your-folder-id> \\
    LLM_BASE_URL=http://<your-lan-ip>:20128/v1 \\
    LLM_API_KEY=<your-llm-key> \\
    LLM_MODEL=<model-name> \\
    python build_flows.py
"""
import json, os, requests, sys

LANGFLOW_URL  = os.getenv("LANGFLOW_URL",       "http://127.0.0.1:7860")
API_KEY       = os.getenv("LANGFLOW_API_KEY",   "")
FOLDER_ID     = os.getenv("LANGFLOW_FOLDER_ID", "")
LLM_BASE_URL  = os.getenv("LLM_BASE_URL",       "http://localhost:20128/v1")
LLM_API_KEY   = os.getenv("LLM_API_KEY",        "")
LLM_MODEL     = os.getenv("LLM_MODEL",          "semua_model_dipilih")

if not API_KEY:
    print("ERROR: Set LANGFLOW_API_KEY environment variable")
    sys.exit(1)

HEADERS = {"x-api-key": API_KEY}

def get_registry():
    r = requests.get(f"{LANGFLOW_URL}/api/v1/all", headers=HEADERS, timeout=30)
    r.raise_for_status()
    return r.json()

def make_node(node_id, data_type, node_template, x, y):
    return {
        "id": node_id,
        "type": "genericNode",
        "dragging": False,
        "selected": False,
        "position": {"x": x, "y": y},
        "measured": {"width": 240, "height": 280},
        "data": {
            "id": node_id,
            "type": data_type,
            "showNode": True,
            "node": node_template
        }
    }

def make_edge(edge_id, src, tgt, src_handle, tgt_handle):
    """Build an edge with both the top-level string handles AND a nested 'data' dict.

    Langflow's _get_edges_as_list_of_tuples() reads:
        e["data"]["sourceHandle"]["id"]
        e["data"]["targetHandle"]["id"]
    So edges MUST have a 'data' key with parsed (dict) sourceHandle/targetHandle.
    The top-level sourceHandle/targetHandle strings are kept for UI compatibility.
    """
    return {
        "id": edge_id,
        "source": src,
        "target": tgt,
        # Top-level string handles (UI rendering)
        "sourceHandle": json.dumps(src_handle, separators=(',', ':')),
        "targetHandle": json.dumps(tgt_handle, separators=(',', ':')),
        # Nested data dict with PARSED handles (required by graph builder)
        "data": {
            "sourceHandle": src_handle,
            "targetHandle": tgt_handle,
        },
        "animated": False,
        "selected": False,
    }

def build_flow(flow_id, name, description, system_prompt, prompt_template_text, ci_label, ci_info):
    """Build a complete flow JSON with proper Langflow component structure.

    Uses ext:openai:OpenAIModelComponent@official (NOT LanguageModelComponent) because
    LanguageModelComponent has external_options.fields['data'] which causes KeyError('data')
    in Langflow's graph builder when the model.value is not a proper model-object.
    """
    import copy
    reg = get_registry()

    # Get base component templates from registry
    ci_tmpl  = copy.deepcopy(reg["input_output"]["ChatInput"])
    pt_tmpl  = copy.deepcopy(reg["models_and_agents"]["Prompt Template"])
    llm_tmpl = copy.deepcopy(reg["openai"]["ext:openai:OpenAIModelComponent@official"])
    co_tmpl  = copy.deepcopy(reg["input_output"]["ChatOutput"])

    # Add lf_version to each template — required by Langflow graph builder at runtime
    for tmpl in (ci_tmpl, pt_tmpl, llm_tmpl, co_tmpl):
        tmpl["lf_version"] = "1.11.6"

    # --- ChatInput node ---
    ci_tmpl["display_name"] = ci_label
    ci_tmpl["minimized"] = False
    ci_tmpl["template"]["input_value"]["display_name"] = ci_label
    ci_tmpl["template"]["input_value"]["info"] = ci_info

    # --- Prompt Template node ---
    pt_tmpl["display_name"] = "Prompt Template"
    pt_tmpl["template"]["template"]["value"] = prompt_template_text
    # Add the {input} variable field matching the {input} placeholder in the template.
    # This is a dynamic field — Langflow generates these from the template text.
    # We must declare it explicitly so the graph builder can resolve it.
    pt_tmpl["template"]["input"] = {
        "advanced": False,
        "api_editable": False,
        "display_name": "input",
        "dynamic": False,
        "field_type": "str",
        "fileTypes": [],
        "file_path": "",
        "info": "",
        "input_types": ["Message"],
        "list": False,
        "load_from_db": False,
        "multiline": True,
        "name": "input",
        "placeholder": "",
        "required": False,
        "show": True,
        "title_case": False,
        "type": "str",
        "value": ""
    }

    # --- OpenAIModelComponent node ---
    # This component has openai_api_base + model_name string fields, no ModelInput/external_options.
    llm_tmpl["display_name"] = "Language Model (Localhost)"
    llm_tmpl["template"]["api_key"]["value"] = LLM_API_KEY
    llm_tmpl["template"]["api_key"]["load_from_db"] = False
    llm_tmpl["template"]["openai_api_base"]["value"] = LLM_BASE_URL
    llm_tmpl["template"]["model_name"]["value"] = LLM_MODEL
    llm_tmpl["template"]["temperature"]["value"] = 0.1
    llm_tmpl["template"]["max_tokens"]["value"] = 4096
    llm_tmpl["template"]["system_message"]["value"] = system_prompt
    llm_tmpl["template"]["stream"]["value"] = False

    # --- ChatOutput node ---
    co_tmpl["display_name"] = "Chat Output"
    co_tmpl["template"]["sender_name"]["value"] = "FlowFix-AI"

    LLM_COMPONENT_TYPE = "ext:openai:OpenAIModelComponent@official"

    # Build nodes
    nodes = [
        make_node("ci-main",   "ChatInput",         ci_tmpl,  80,  300),
        make_node("prompt-pc", "Prompt Template",   pt_tmpl,  500, 300),
        make_node("llm-pc",    LLM_COMPONENT_TYPE,  llm_tmpl, 940, 300),
        make_node("co-pc",     "ChatOutput",        co_tmpl,  1380, 300),
    ]

    # Build edges
    edges = [
        make_edge("e1", "ci-main", "prompt-pc",
            {"dataType": "ChatInput", "id": "ci-main", "name": "message", "output_types": ["Message"]},
            {"fieldName": "input", "id": "prompt-pc", "inputTypes": ["Message", "Text"], "type": "str"}),
        make_edge("e2", "prompt-pc", "llm-pc",
            {"dataType": "Prompt Template", "id": "prompt-pc", "name": "prompt", "output_types": ["Message"]},
            {"fieldName": "input_value", "id": "llm-pc", "inputTypes": ["Message"], "type": "str"}),
        make_edge("e3", "llm-pc", "co-pc",
            {"dataType": LLM_COMPONENT_TYPE, "id": "llm-pc", "name": "text_output", "output_types": ["Message"]},
            {"fieldName": "input_value", "id": "co-pc", "inputTypes": ["Data", "JSON", "DataFrame", "Table", "Message"], "type": "other"}),
    ]

    flow = {
        "id": flow_id,
        "name": name,
        "description": description,
        "folder_id": FOLDER_ID,
        "data": {
            "nodes": nodes,
            "edges": edges,
            "viewport": {"x": 0, "y": 0, "zoom": 0.75}
        }
    }
    return flow

def upload_flow(flow_dict):
    """Delete existing flow (if any) and upload the new one."""
    flow_id = flow_dict.get("id")
    if flow_id:
        try:
            requests.delete(f"{LANGFLOW_URL}/api/v1/flows/{flow_id}", headers=HEADERS, timeout=10)
            print(f"  Deleted old flow {flow_id}")
        except Exception as e:
            print(f"  Delete skipped: {e}")

    flow_json = json.dumps(flow_dict, ensure_ascii=False)
    files = {"file": (f"{flow_dict['name']}.json", flow_json.encode("utf-8"), "application/json")}
    r = requests.post(
        f"{LANGFLOW_URL}/api/v1/flows/upload/?folder_id={FOLDER_ID}",
        headers=HEADERS,
        files=files,
        timeout=30
    )
    r.raise_for_status()
    result = r.json()
    new_id = result[0]["id"] if isinstance(result, list) else result.get("id")
    print(f"  Uploaded: {flow_dict['name']} -> ID={new_id}")
    return new_id

def test_flow(flow_id, test_input):
    """Quick smoke-test a flow."""
    body = {"input_value": test_input, "input_type": "chat", "output_type": "chat"}
    r = requests.post(
        f"{LANGFLOW_URL}/api/v1/run/{flow_id}",
        headers={**HEADERS, "Content-Type": "application/json"},
        json=body,
        timeout=120
    )
    if r.status_code == 200:
        data = r.json()
        text = data["outputs"][0]["outputs"][0]["results"]["message"]["data"]["text"]
        return True, text[:300]
    else:
        return False, r.text[:300]


# ============================================================
# FLOW DEFINITIONS
# ============================================================

FLOWS = [
    {
        "id": "492d4197-127b-4d03-957b-391d6e458609",
        "name": "FlowFix App - document-precheck",
        "description": "Pre-check kelengkapan field dan konsistensi dokumen pengajuan magang.",
        "ci_label": "Input (SOP + Form + Docs)",
        "ci_info": "Kirim JSON: {sop_requirements, form_data, document_texts} atau teks gabungan.",
        "system_prompt": (
            "Kamu adalah FlowFix Document Pre-Check Agent. "
            "Tugas: periksa kelengkapan field wajib dan konsistensi data antar-dokumen pengajuan magang. "
            "Output HARUS berupa JSON valid:\n"
            '{"status": "PASS"|"NEEDS_FIX"|"NEEDS_HUMAN_CHECK", '
            '"findings": [{"rule": "R01", "issue": "..."}], '
            '"human_checks": ["..."]}'
        ),
        "prompt_template": (
            "{input}\n\n"
            "---\n"
            "Berdasarkan SOP, data formulir, dan teks dokumen di atas, "
            "periksa kelengkapan dan konsistensi pengajuan magang. "
            "Kembalikan HANYA JSON valid."
        ),
        "test_input": (
            "## SOP\nR01: Lima lampiran wajib. R06: Min 90 SKS IPK 2.75. "
            "R12: PASS jika semua lulus.\n\n"
            "## FORMULIR\n{\"nim\":\"IF240017\",\"name\":\"Arga Pratama\","
            "\"company\":\"PT Lentera Digital\",\"semester\":6}\n\n"
            "## DOKUMEN\n5 dokumen lengkap. Transkrip 100 SKS IPK 3.50. Semua data konsisten."
        )
    },
    {
        "id": "dbaa87ec-27fe-4382-99a9-4278d6700bbb",
        "name": "FlowFix App - reviewer-summary",
        "description": "Ringkasan terstruktur pengajuan untuk reviewer admin/dosen.",
        "ci_label": "Input (Submission + Docs + Pre-Check)",
        "ci_info": "Kirim JSON: {submission_data, document_texts, precheck_result}.",
        "system_prompt": (
            "Kamu adalah FlowFix Reviewer Summary Generator. "
            "Buat ringkasan terstruktur dari pengajuan mahasiswa untuk mempercepat review admin/dosen. "
            "Output HARUS berupa JSON valid:\n"
            '{"student": {}, "company": {}, "period": {}, '
            '"documents": [], "key_facts": [], "human_verification_required": []}'
        ),
        "prompt_template": (
            "{input}\n\n"
            "---\n"
            "Buat ringkasan terstruktur untuk reviewer berdasarkan data di atas. "
            "Kembalikan HANYA JSON valid."
        ),
        "test_input": (
            "## DATA PENGAJUAN\n{\"nim\":\"IF240017\",\"name\":\"Arga Pratama\","
            "\"company\":\"PT Lentera Digital\",\"semester\":6}\n\n"
            "## DOKUMEN\n5 dokumen lengkap. IPK 3.50.\n\n"
            "## HASIL PRE-CHECK\n{\"status\":\"PASS\",\"findings\":[]}"
        )
    },
    {
        "id": "fd6a11cd-e579-4744-a2ab-a35569c91b46",
        "name": "FlowFix App - revision-planner",
        "description": "Root cause analysis revisi dan usulan koreksi spesifik.",
        "ci_label": "Input (Reviewer Comment + Submission + Evidence + SOP)",
        "ci_info": "Kirim JSON: {reviewer_comment, current_submission, updated_evidence, sop_rules}.",
        "system_prompt": (
            "Kamu adalah FlowFix Recovery Agent. "
            "Bantu mahasiswa memahami dan memperbaiki revisi dengan root cause analysis. "
            "Output HARUS berupa JSON valid:\n"
            '{"root_cause": "...", '
            '"affected_fields": [{"field":"...","current_value":"...","proposed_value":"...","evidence":"..."}], '
            '"correction_steps": ["..."]}'
        ),
        "prompt_template": (
            "{input}\n\n"
            "---\n"
            "Lakukan root cause analysis dan susun rencana koreksi. "
            "Kembalikan HANYA JSON valid."
        ),
        "test_input": (
            "## KOMENTAR REVIEWER\nPeriode magang tidak konsisten antar dokumen.\n\n"
            "## PENGAJUAN\n{\"start\":\"2027-01-11\",\"end\":\"2027-04-09\"}\n\n"
            "## BUKTI\nSurat penerimaan: Jan-Apr 2027. Formulir: Feb-Apr 2027.\n\n"
            "## SOP\nR04: Semua periode harus konsisten."
        )
    },
    {
        "id": "18f5878c-1f9c-4ea6-b6e3-ab1e20e97cda",
        "name": "FlowFix App - submission-assistant",
        "description": "Chatbot kontekstual yang mengetahui state pengajuan mahasiswa.",
        "ci_label": "Input (State + Question)",
        "ci_info": "Kirim JSON: {submission_state, student_question} atau pertanyaan langsung.",
        "system_prompt": (
            "Kamu adalah FlowFix Submission Assistant. "
            "Kamu mengetahui state pengajuan mahasiswa dan menjawab pertanyaan berdasarkan konteks nyata. "
            "Berikan jawaban spesifik dan actionable dalam bahasa yang mudah dipahami."
        ),
        "prompt_template": (
            "{input}\n\n"
            "---\n"
            "Jawab pertanyaan mahasiswa berdasarkan state pengajuan di atas. "
            "Berikan jawaban yang spesifik dan actionable."
        ),
        "test_input": (
            "## STATE PENGAJUAN\n{\"status\":\"NEEDS_FIX\","
            "\"missing\":[\"AdvisorApproval semester field\"]}\n\n"
            "## PERTANYAAN\nKenapa pengajuanku belum bisa submit?"
        )
    },
]

if __name__ == "__main__":
    # Check if LanguageModelComponent exists in registry
    reg = get_registry()
    if "LanguageModelComponent" not in reg.get("models_and_agents", {}):
        print("ERROR: LanguageModelComponent not found in registry!")
        print("Available models_and_agents:", list(reg.get("models_and_agents", {}).keys()))
        sys.exit(1)
    print("LanguageModelComponent found in registry OK")
    print(f"LLM template fields: {list(reg['models_and_agents']['LanguageModelComponent']['template'].keys())}")

    results = []
    for flow_def in FLOWS:
        print(f"\n--- Building: {flow_def['name']} ---")
        flow = build_flow(
            flow_id=flow_def["id"],
            name=flow_def["name"],
            description=flow_def["description"],
            system_prompt=flow_def["system_prompt"],
            prompt_template_text=flow_def["prompt_template"],
            ci_label=flow_def["ci_label"],
            ci_info=flow_def["ci_info"]
        )
        new_id = upload_flow(flow)
        results.append((flow_def["name"], new_id))

    print("\n\n=== ALL FLOWS UPLOADED ===")
    for name, fid in results:
        print(f"  {name}: {fid}")

    # Test TC01 on document-precheck
    print("\n\n=== TESTING TC01 on document-precheck ===")
    tc01_id = results[0][1]
    ok, response = test_flow(tc01_id, FLOWS[0]["test_input"])
    if ok:
        print(f"SUCCESS! AI Response:\n{response}")
    else:
        print(f"FAILED: {response}")
