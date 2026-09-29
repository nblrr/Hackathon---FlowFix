<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Services\{SubmissionService,LangflowService,PrecheckService,RevisionService};
use App\Exceptions\ApiException;
class AnalysisController extends Controller {
    public function __construct(private SubmissionService $service,private LangflowService $langflow,private RevisionService $revisions) {}
    public function messages(ApiRequest $r,Submission $submission) {
        $this->service->authorize($submission,$r->user(),student:true); $this->service->version($submission,$r->integer('data_version'));
        $message=$submission->messages()->create(['speaker'=>'student','content'=>$r->validated('message')]);
        $state=$this->service->snapshot($submission);
        $state['precheck_result']=$submission->prechecks()->where('data_version',$submission->data_version)->where('status','COMPLETED')->latest('id')->first()?->result;
        $state['reviewer_feedback']=$submission->reviews()->latest('id')->first()?->toArray();
        $state['conversation']=$submission->messages()->get(['speaker','content'])->toArray();
        $state['document_evidence']=$this->service->documents($submission);
        try { $output=$this->langflow->run('submission-assistant',['submission_state'=>$state,'sop_context'=>$this->service->sop(),'user_message'=>$r->validated('message')],'submission-'.$submission->id); }
        catch(ApiException $e) { $message->update(['metadata'=>['error_code'=>$e->errorCode]]); throw $e; }
        $this->service->locked($submission,$r->integer('data_version'),function($s) use($output) {
            $s->messages()->create(['speaker'=>'assistant','content'=>$output['message'],'metadata'=>['intent'=>$output['intent'],'next_action'=>$output['next_action'],'data_version'=>$s->data_version]]);
            if(!empty($output['proposed_updates'])&&in_array($s->status,['DRAFT','REVISION_REQUESTED'])) {
                $changes=[];
                foreach($output['proposed_updates'] as $field=>$value) $changes[]=['field'=>$field,'current_value'=>$s->form_data[$field]??null,'proposed_value'=>$value,'reason'=>'Informasi dari percakapan. Periksa sebelum menyimpan.','evidence_source'=>null,'evidence'=>null,'selectable'=>true,'external_action'=>'Perubahan isian tidak mengubah PDF.'];
                $s->plans()->create(['source'=>'chat','data_version'=>$s->data_version,'snapshot'=>$this->service->snapshot($s),'proposal'=>['root_cause'=>'Konfirmasi informasi dari percakapan','affected_fields'=>$changes,'recommended_changes'=>[],'needs_human_review'=>false]]);
            }
        });
        return new SubmissionResource($submission->refresh());
    }
    public function precheck(ApiRequest $r,Submission $submission,PrecheckService $precheck) {
        $this->service->authorize($submission,$r->user(),student:true); $this->service->editable($submission); $this->service->version($submission,$r->integer('data_version'));
        $precheck->run($submission); return new SubmissionResource($submission->refresh());
    }
    public function summary(ApiRequest $r,Submission $submission) {
        $this->service->authorize($submission,$r->user(),reviewer:true); $this->service->version($submission,$r->integer('data_version'));
        $result=$this->langflow->run('reviewer-summary',['submission_data'=>$this->service->snapshot($submission)+['sop'=>$this->service->sop()],'document_contents'=>$this->service->documents($submission),'precheck_result'=>$submission->prechecks()->where('data_version',$submission->data_version)->where('status','COMPLETED')->latest('id')->first()?->result??[]],'submission-'.$submission->id);
        if($result['submission_status']!==$submission->status) throw \App\Data\AiOutput::invalid();
        foreach($result['key_facts'] as &$fact) {
            $doc=$submission->documents()->where('is_current',true)->where('original_filename',$fact['source_document']??'')->first();
            $fact['source_verified']=$doc!==null && (($fact['page']??null)===null || collect($doc->pages)->contains('page',$fact['page']));
            if(!$fact['source_verified']) $fact['page']=null;
        }
        unset($fact);
        $submission->summaries()->create(['data_version'=>$submission->data_version,'result'=>$result]);
        return new SubmissionResource($submission->refresh());
    }
    public function plan(ApiRequest $r,Submission $submission) {
        $this->service->authorize($submission,$r->user(),student:true); $this->service->version($submission,$r->integer('data_version')); $this->revisions->generate($submission);
        return new SubmissionResource($submission->refresh());
    }
    public function apply(ApiRequest $r,Submission $submission,string $planId) {
        $this->service->authorize($submission,$r->user(),student:true); $plan=$submission->plans()->findOrFail($planId);
        $this->revisions->apply($submission,$plan,$r->validated('selected_fields'),$r->integer('data_version'),$r->user());
        return new SubmissionResource($submission->refresh());
    }
}
