<?php
namespace App\Services;
use App\Models\Submission;
use App\Exceptions\ApiException;
class PrecheckService {
    public function __construct(private SubmissionService $submissions, private LangflowService $langflow) {}
    public function run(Submission $s): void {
        $snapshot=$this->submissions->snapshot($s);
        $run=$s->prechecks()->create(['data_version'=>$s->data_version,'sop_version'=>$s->sop_version,'status'=>'RUNNING','snapshot'=>$snapshot]);
        try {
            $ai=$this->langflow->run('document-precheck',['sop_requirements'=>$this->submissions->sop(),'form_data'=>$s->form_data,'document_contents'=>$this->submissions->documents($s)],'submission-'.$s->id);
            $result=$this->submissions->findings($s); $uncertain=false;
            foreach($ai['findings'] as $f) {
                $doc=$s->documents()->where('is_current',true)->where('original_filename',$f['document']??'')->first();
                $evidence=$f['evidence']??null;
                $page=app(EvidenceService::class)->page($doc,$evidence);
                $references=[];
                foreach($f['evidence_references']??[] as $ref) {
                    $refDoc=$s->documents()->where('is_current',true)->where('original_filename',$ref['document'])->first();
                    $refPage=app(EvidenceService::class)->page($refDoc,$ref['quote']);
                    $references[]=['document'=>$ref['document'],'document_id'=>$refDoc?->id,'quote'=>$ref['quote'],'page'=>$refPage,'verified'=>$refPage!==null];
                }
                $verified=$references ? collect($references)->every(fn($r)=>$r['verified']) && collect($references)->contains('document',$f['document']??null) : $page!==null;
                if($verified && $page===null) $page=collect($references)->firstWhere('document',$f['document'])['page']??null;
                // Never present an unverifiable quotation as verified evidence.
                $certain=$verified && $doc?->extraction_status==='ready' && !in_array($f['type'],['unreadable','missing_input','other']);
                $finding=['source'=>'ai','severity'=>$certain?'error':'uncertain','document'=>$f['document']??null,'document_id'=>$doc?->id,'field'=>$f['field']??null,'issue'=>$f['message'],'rule'=>$f['rule']??$this->rule($f['type']),'evidence'=>$evidence,'evidence_verified'=>$verified,'evidence_references'=>$references,'page'=>$page,'current_value'=>$f['current_value']??null,'conflicting_value'=>$f['conflicting_value']??null,'suggested_action'=>$f['suggested_action']??($certain?'Koreksi nilai yang berbeda pada isian atau unggah dokumen pengganti.':'Periksa dokumen bersama peninjau; bukti AI belum dapat dipastikan.')];
                $result[$certain?'findings':'human_checks'][]=$finding; if(!$certain) $uncertain=true;
            }
            $result['human_checks'][]=['source'=>'deterministic','severity'=>'info','document'=>null,'field'=>null,'issue'=>'Keaslian dokumen, wewenang, dan tanda tangan harus diverifikasi peninjau.','rule'=>'R08 / R12','evidence'=>null,'suggested_action'=>'Verifikasi manusia sebelum persetujuan.','page'=>null];
            $result['status']=$result['findings']?'NEEDS_FIX':(($uncertain||$ai['status']!=='PASS'||count($result['human_checks'])>1)?'NEEDS_HUMAN_CHECK':'PASS');
            $result['summary']=$ai['summary']; $result['ai_status']=$ai['status']; $result['documents_checked']=$snapshot['documents'];
            $run->update(['status'=>'COMPLETED','result'=>$result]);
        } catch(ApiException $e) { $run->update(['status'=>'FAILED','error_code'=>$e->errorCode]); throw $e; }
    }
    private function rule(string $type): string { return match($type) {'missing_document'=>'R01','mismatch'=>'R03 / R04','invalid_value'=>'R02–R08','unreadable','missing_input'=>'R09',default=>'Perlu verifikasi SOP'}; }
}
