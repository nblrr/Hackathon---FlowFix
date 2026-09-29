"""Langflow 1.12 component: uploaded PDFs to the pre-check document contract."""

import json
from copy import deepcopy
from pathlib import PurePosixPath

from lfx.components.files_and_knowledge.file import FileComponent
from lfx.io import Output, StrInput
from lfx.schema.message import Message


class FlowFixPDFInput(FileComponent):
    display_name = "Upload PDF"
    description = "Upload PDF untuk diperiksa. Teks dan nama file diteruskan otomatis."
    name = "FlowFixPDFInput"
    inputs = deepcopy(FileComponent.inputs) + [
        StrInput(
            name="document_type",
            display_name="Jenis Dokumen",
            value="surat_penerimaan_magang",
            required=True,
            info="Jenis dokumen sesuai SOP. Semua PDF pada node ini memakai jenis yang sama.",
        ),
    ]
    outputs = [
        Output(display_name="Document Contents", name="message", method="document_contents"),
    ]

    def update_outputs(self, frontend_node, field_name, field_value):
        # Keep the JSON message port stable when the user selects another PDF.
        return frontend_node

    def document_contents(self) -> Message:
        table = self.load_files()
        rows = table.to_dict(orient="records")
        documents = {}
        for row in rows:
            path = str(row.get("file_path") or row.get("path") or "")
            filename = PurePosixPath(path.replace("\\", "/")).name
            if not filename:
                raise ValueError("Nama file PDF tidak tersedia dari pembaca dokumen.")
            document = documents.setdefault(path, {
                "filename": filename,
                "document_type": self.document_type,
                "content": "",
            })
            content = row.get("text", "")
            if isinstance(content, str) and content.strip():
                document["content"] += ("\n\n" if document["content"] else "") + content.strip()
        if not documents:
            raise ValueError("Pilih minimal satu PDF di kolom Files sebelum menjalankan flow.")
        for document in documents.values():
            if not document["content"]:
                document["content"] = "[UNREADABLE]"
        result = Message(text=json.dumps(list(documents.values()), ensure_ascii=False))
        self.status = result
        return result
