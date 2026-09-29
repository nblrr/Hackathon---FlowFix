<?php
namespace App\Services;
use Symfony\Component\Process\Process;
class DocumentExtractionService {
    public function extract(string $path): array {
        try {
            $p=new Process([config('flowfix.python'),base_path('scripts/extract_pdf.py'),$path]);
            $p->setTimeout(config('flowfix.extraction_timeout')); $p->mustRun();
            $result=json_decode($p->getOutput(),true,512,JSON_THROW_ON_ERROR);
            if(!isset($result['status'],$result['pages'])) throw new \RuntimeException();
            return $result;
        } catch(\Throwable $e) { return ['status'=>'failed','code'=>'EXTRACTION_FAILED','message'=>'Ekstraksi gagal atau melewati batas waktu. Coba PDF lain atau hubungi pengelola.','pages'=>[]]; }
    }
}
