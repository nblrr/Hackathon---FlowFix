import requests, json, os

LANGFLOW_URL = os.getenv("LANGFLOW_URL",     "http://127.0.0.1:7860")
API_KEY      = os.getenv("LANGFLOW_API_KEY", "")
H = {"x-api-key": API_KEY, "Content-Type": "application/json"}

def run_flow(flow_id, user_input):
    r = requests.post(
        f"{LANGFLOW_URL}/api/v1/run/{flow_id}",
        headers=H,
        json={"input_value": user_input, "input_type": "chat", "output_type": "chat"},
        timeout=120,
    )
    if r.status_code == 200:
        return True, r.json()["outputs"][0]["outputs"][0]["results"]["message"]["data"]["text"][:300]
    return False, r.text[:200]

TESTS = [
    (
        "document-precheck",
        "492d4197-127b-4d03-957b-391d6e458609",
        "SOP: R01 lima lampiran wajib. R06 min 90 SKS IPK 2.75. FORMULIR: nim=IF240017, name=Arga Pratama. DOKUMEN: 5 dokumen lengkap dan konsisten. Transkrip 100 SKS IPK 3.50.",
    ),
    (
        "reviewer-summary",
        "dbaa87ec-27fe-4382-99a9-4278d6700bbb",
        "DATA PENGAJUAN: nim=IF240017, name=Arga Pratama, company=PT Lentera Digital. DOKUMEN: CV+Transkrip IPK 3.50+Surat+PA+Formulir. HASIL PRECHECK: status=PASS, findings=[].",
    ),
    (
        "revision-planner",
        "fd6a11cd-e579-4744-a2ab-a35569c91b46",
        "KOMENTAR REVIEWER: Periode magang tidak konsisten antar dokumen. PENGAJUAN: start=Jan 2027, end=Apr 2027. BUKTI: Surat penerimaan Feb-Apr 2027. SOP R04: semua periode harus konsisten.",
    ),
    (
        "submission-assistant",
        "18f5878c-1f9c-4ea6-b6e3-ab1e20e97cda",
        "STATE PENGAJUAN: status=NEEDS_FIX, missing_fields=[semester_field_in_PA_document]. PERTANYAAN MAHASISWA: Kenapa pengajuanku belum bisa submit ke admin?",
    ),
]

all_ok = True
for name, flow_id, user_input in TESTS:
    ok, response = run_flow(flow_id, user_input)
    status = "OK" if ok else "FAIL"
    print(f"\n[{status}] {name}")
    print(response)
    if not ok:
        all_ok = False

print("\n" + ("=== ALL 4 FLOWS OK ===" if all_ok else "=== SOME FLOWS FAILED ==="))
