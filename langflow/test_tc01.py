import requests, json, os

LANGFLOW_URL   = os.getenv("LANGFLOW_URL",      "http://127.0.0.1:7860")
API_KEY        = os.getenv("LANGFLOW_API_KEY",  "")
FLOW_ID        = os.getenv("PRECHECK_FLOW_ID",  "492d4197-127b-4d03-957b-391d6e458609")

# Cari TC01.json relatif dari script ini (FlowFix-Test-Pack/payload_langflow/)
_here = os.path.dirname(os.path.abspath(__file__))
TC01_PATH = os.path.join(_here, "..", "FlowFix-Test-Pack", "payload_langflow", "TC01.json")

with open(TC01_PATH, encoding="utf-8") as f:
    tc01 = json.load(f)

combined = (
    "## PERSYARATAN SOP\n"
    + tc01["sop_requirements"]
    + "\n\n## DATA FORMULIR\n"
    + tc01["form_data"]
    + "\n\n## TEKS DOKUMEN\n"
    + tc01["document_texts"]
)

print(f"Input length: {len(combined)} chars")

r = requests.post(
    f"{LANGFLOW_URL}/api/v1/run/{FLOW_ID}",
    headers={"x-api-key": API_KEY, "Content-Type": "application/json"},
    json={"input_value": combined, "input_type": "chat", "output_type": "chat"},
    timeout=120,
)

if r.status_code == 200:
    text = r.json()["outputs"][0]["outputs"][0]["results"]["message"]["data"]["text"]
    print("TC01 FULL TEST SUCCESS:")
    print(text[:1000])
    # Strip markdown code fences if present
    clean = text.strip()
    if clean.startswith("```"):
        clean = clean.split("\n", 1)[1].rsplit("```", 1)[0].strip()
    try:
        result = json.loads(clean)
        print(f"\nStatus: {result.get('status')}")
        print(f"Findings ({len(result.get('findings', []))}): {result.get('findings', [])[:2]}")
        print(f"Human checks ({len(result.get('human_checks', []))}): {result.get('human_checks', [])[:2]}")
    except Exception as e:
        print(f"(JSON parse error: {e})")
else:
    print(f"FAILED ({r.status_code}): {r.text[:400]}")
