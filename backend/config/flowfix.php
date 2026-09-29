<?php
return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    'debug' => (bool) env('FLOWFIX_DEBUG', false),
    'max_upload_kb' => (int) env('FLOWFIX_MAX_UPLOAD_KB', 10240),
    'max_context_chars' => (int) env('FLOWFIX_MAX_CONTEXT_CHARS', 120000),
    'python' => env('FLOWFIX_PYTHON', 'python'),
    'extraction_timeout' => (int) env('FLOWFIX_EXTRACTION_TIMEOUT_SECONDS', 30),
    'sop_version' => 'SIMULASI-1.0',
    'sop_path' => base_path('../FlowFix-Test-Pack/sop_simulasi.txt'),
    'fields' => ['student_name'=>'Nama lengkap', 'nim'=>'NIM', 'study_program'=>'Program studi', 'university'=>'Perguruan tinggi', 'semester'=>'Semester', 'academic_year'=>'Tahun akademik', 'company_name'=>'Perusahaan tujuan', 'start_date'=>'Tanggal mulai', 'end_date'=>'Tanggal selesai', 'advisor_name'=>'Dosen pembimbing', 'submission_date'=>'Tanggal pengajuan', 'internship_scheme'=>'Skema magang'],
    'required_fields' => ['student_name','nim','study_program','semester','academic_year','company_name','start_date','end_date','advisor_name','submission_date'],
    'documents' => ['application_form'=>'Formulir pengajuan', 'cv'=>'CV', 'transcript'=>'Transkrip akademik', 'acceptance_letter'=>'Surat penerimaan', 'advisor_approval'=>'Persetujuan PA', 'other'=>'Dokumen tambahan'],
    'required_documents' => ['application_form','cv','transcript','acceptance_letter','advisor_approval'],
    'langflow' => [
        'base_url' => env('LANGFLOW_BASE_URL', 'http://127.0.0.1:7860'),
        'api_key' => env('LANGFLOW_API_KEY'),
        'connect_timeout' => (int) env('LANGFLOW_CONNECT_TIMEOUT_SECONDS', 5),
        'run_timeout' => (int) env('LANGFLOW_RUN_TIMEOUT_SECONDS', 90),
        'flows' => [
            'submission-assistant' => env('LANGFLOW_SUBMISSION_ASSISTANT_ID'),
            'document-precheck' => env('LANGFLOW_DOCUMENT_PRECHECK_ID'),
            'reviewer-summary' => env('LANGFLOW_REVIEWER_SUMMARY_ID'),
            'revision-planner' => env('LANGFLOW_REVISION_PLANNER_ID'),
        ],
        'mapping' => [
            'submission-assistant'=>['submission_state'=>'ChatInput-FH6gN','user_message'=>'ChatInput-qsq4H','sop_context'=>'ChatInput-bInbW'],
            'document-precheck'=>['sop_requirements'=>'TextInput-SOP','form_data'=>'TextInput-Form','document_contents'=>'TextInput-Documents'],
            'reviewer-summary'=>['submission_data'=>'ChatInput-hXWlc','document_contents'=>'ChatInput-CMt4x','precheck_result'=>'ChatInput-ZlsyZ'],
            'revision-planner'=>['reviewer_comment'=>'ChatInput-XBK3f','current_submission'=>'ChatInput-0jSlh','updated_evidence'=>'ChatInput-OKena','sop_rules'=>'ChatInput-1lVbI'],
        ],
    ],
];
