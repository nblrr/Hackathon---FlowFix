<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\DB;
class SubmissionController extends Controller {
    public function __construct(private SubmissionService $service) {}
    public function configuration(ApiRequest $r) {
        $config=collect(config('flowfix'))->only(['fields','required_fields','documents','required_documents','max_upload_kb','sop_version','debug'])->all();
        $config['sop']=$this->service->sop();
        $pack=json_decode(file_get_contents(base_path('../FlowFix-Test-Pack/test_cases.json')),true);
        $config['demo_cases']=array_map(fn($c)=>['id'=>$c['id'],'title'=>$c['title'],'form_data'=>$c['form_data'],'documents'=>$c['documents']],$pack['cases']);
        return ['data'=>$config];
    }
    public function index(ApiRequest $r) {
        return ['data'=>Submission::query()->when($r->user()->role==='student',fn($q)=>$q->where('user_id',$r->user()->id),fn($q)=>$q->where('status','!=','DRAFT'))->latest('updated_at')->get(['id','form_data','status','data_version','updated_at'])];
    }
    public function store(ApiRequest $r) {
        abort_unless($r->user()->role==='student',403);
        $s=DB::transaction(function() use($r) {
            $s=Submission::create(['user_id'=>$r->user()->id,'form_data'=>[],'sop_version'=>config('flowfix.sop_version')]);
            $s->messages()->create(['speaker'=>'assistant','content'=>'Halo, aku FlowFix. Kamu ingin mengurus pengajuan apa?','metadata'=>['source'=>'welcome']]);
            $this->service->audit($s,$r->user(),'created'); return $s;
        });
        return new SubmissionResource($s);
    }
    public function show(ApiRequest $r,Submission $submission) { $this->service->authorize($submission,$r->user()); return new SubmissionResource($submission); }
    public function update(ApiRequest $r,Submission $submission) {
        $this->service->authorize($submission,$r->user(),student:true);
        $this->service->locked($submission,$r->integer('data_version'),function($s) use($r) { $this->service->editable($s); $this->service->changeFields($s,$r->validated('form_data'),$r->user()); });
        return new SubmissionResource($submission->refresh());
    }
    public function submit(ApiRequest $r,Submission $submission) {
        $this->service->authorize($submission,$r->user(),student:true);
        $this->service->submit($submission,$r->integer('data_version'),$r->user(),$r->route()->getName()==='resubmit',$r->boolean('human_review'));
        return new SubmissionResource($submission->refresh());
    }
}
