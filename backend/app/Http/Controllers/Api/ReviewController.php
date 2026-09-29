<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Services\SubmissionService;
use App\Exceptions\ApiException;
class ReviewController extends Controller {
    public function __invoke(ApiRequest $r,Submission $submission,SubmissionService $service) {
        $service->authorize($submission,$r->user(),reviewer:true);
        $service->locked($submission,$r->integer('data_version'),function($s) use($r,$service) {
            $prior=$s->reviews()->where('data_version',$s->data_version)->first();
            if($prior && $prior->decision===$r->validated('decision') && $prior->comment===$r->validated('comment')) return;
            if(!in_array($s->status,['SUBMITTED','UNDER_REVIEW'])) throw new ApiException('INVALID_TRANSITION','Hanya pengajuan terkirim yang dapat diputuskan.',409);
            $s->reviews()->create(['user_id'=>$r->user()->id,'data_version'=>$s->data_version,'decision'=>$r->validated('decision'),'comment'=>$r->validated('comment')]);
            $s->update(['status'=>$r->validated('decision')]);
            if($s->status==='REVISION_REQUESTED') $s->increment('data_version');
            $service->audit($s,$r->user(),'review_decided',['decision'=>$s->status,'comment'=>$r->validated('comment')]);
            $s->messages()->create(['speaker'=>'reviewer','content'=>$r->validated('comment')]);
        });
        return new SubmissionResource($submission->refresh());
    }
}
