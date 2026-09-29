<?php
namespace App\Services;
use App\Models\{Submission, RevisionPlan, User};
use App\Data\AiOutput;
use App\Exceptions\ApiException;
class RevisionService {
    public function __construct(private SubmissionService $submissions,private LangflowService $langflow) {}
    public function generate(Submission $s): void {
        if($s->status!=='REVISION_REQUESTED') throw new ApiException('INVALID_TRANSITION','Rencana revisi tersedia setelah peninjau meminta perbaikan.',409);
        $snapshot=$this->submissions->snapshot($s);
        $proposal=$this->langflow->run('revision-planner',['reviewer_comment'=>$s->reviews()->latest('id')->firstOrFail()->comment,'current_submission'=>$snapshot,'updated_evidence'=>$this->submissions->documents($s,true),'sop_rules'=>$this->submissions->sop()],'submission-'.$s->id);
        if($proposal['submission_id']!==(string)$s->id||$proposal['submission_version']!==$s->data_version) throw AiOutput::invalid();
        $seen=[];
        foreach($proposal['affected_fields'] as &$change) {
            if(!array_key_exists($change['field'],config('flowfix.fields'))||in_array($change['field'],$seen)) throw AiOutput::invalid();
            $seen[]=$change['field'];
            if((string)($change['current_value']??'')!==(string)($s->form_data[$change['field']]??'')) throw AiOutput::invalid();
            $doc=$s->documents()->where('is_current',true)->where('original_filename',$change['evidence_source']??'')->first();
            $change['page']=app(EvidenceService::class)->page($doc,$change['evidence']??null);
            $change['selectable']=$change['page']!==null;
            $change['reason']=$proposal['root_cause'];
            $change['external_action']='Koreksi isian tidak mengubah PDF. Dapatkan persetujuan dan unggah PA/formulir pengganti yang sesuai bila masih memuat nilai lama.';
        }
        unset($change);
        $s->plans()->create(['data_version'=>$s->data_version,'snapshot'=>$snapshot,'proposal'=>$proposal]);
    }
    public function apply(Submission $s, RevisionPlan $plan, array $selected, int $version, User $user): void {
        $this->submissions->locked($s,$version,function($s) use($plan,$selected,$user) {
            $plan->refresh();
            $this->submissions->editable($s);
            if($plan->data_version!==$s->data_version) throw new ApiException('STALE_REVISION','Bukti telah berubah. Buat rencana revisi baru sebelum menerima koreksi.',409);
            if($plan->status!=='PROPOSED') throw new ApiException('PLAN_ALREADY_RESOLVED','Usulan ini sudah diproses.',409);
            $updates=[];
            foreach($plan->proposal['affected_fields'] as $change) if(in_array($change['field'],$selected)) {
                if(!($change['selectable']??false)) throw new ApiException('UNVERIFIED_CHANGE','Bukti koreksi belum terverifikasi. Edit isian secara manual setelah memeriksa dokumen.');
                $updates[$change['field']]=$change['proposed_value'];
            }
            if(count($updates)!==count($selected)) throw new ApiException('INVALID_SELECTION','Pilih hanya kolom yang ada dalam usulan.');
            $this->submissions->changeFields($s,$updates,$user);
            $plan->update(['status'=>$selected?'ACCEPTED':'REJECTED','selected_fields'=>$selected]);
            $this->submissions->audit($s,$user,'revision_confirmed',['plan_id'=>$plan->id,'selected_fields'=>$selected]);
        });
    }
}
