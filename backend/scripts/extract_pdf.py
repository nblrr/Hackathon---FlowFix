"""Local, bounded PDF extractor called by Laravel; this is not an HTTP service."""
import json, logging, pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2] / '.tools' / 'python'))
logging.disable(logging.CRITICAL)
def extract(path):
    from pypdf import PdfReader
    try:
        reader = PdfReader(path, strict=True)
        if reader.is_encrypted:
            return {'status':'failed','code':'PDF_LOCKED','message':'PDF terkunci. Unggah salinan tanpa kata sandi.','pages':[]}
        if not reader.pages or len(reader.pages) > 100:
            return {'status':'failed','code':'PDF_PAGE_LIMIT','message':'PDF harus berisi 1–100 halaman.','pages':[]}
        pages=[]
        for i, page in enumerate(reader.pages):
            try: text=page.extract_text() or ''
            except Exception: text=''
            pages.append({'page':i+1,'text':text})
        partial=any(len(p['text'].strip())<20 for p in pages)
        return {'status':'needs_review' if partial else 'ready','code':'OCR_UNAVAILABLE' if partial else None,'message':'Ada halaman kosong/tidak terbaca. OCR belum tersedia; peninjau perlu memeriksa PDF.' if partial else 'Teks berhasil diekstrak. Validitas administratif belum diperiksa.','pages':pages,'method':'pypdf','ocr_performed':False}
    except Exception:
        return {'status':'failed','code':'PDF_CORRUPT','message':'PDF rusak atau tidak dapat diproses. Ekspor ulang sebagai PDF lalu unggah kembali.','pages':[]}
if __name__ == '__main__':
    try: result=extract(sys.argv[1])
    except ImportError: result={'status':'failed','code':'EXTRACTOR_UNAVAILABLE','message':'Ekstraktor PDF belum terpasang. Hubungi pengelola demo.','pages':[]}
    print(json.dumps(result,ensure_ascii=True))
