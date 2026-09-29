<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Exceptions\ApiException;
use App\Services\{SubmissionService,DocumentExtractionService};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class DocumentController extends Controller {
    public function __construct(private SubmissionService $service,private DocumentExtractionService $extractor) {}
    public function store(ApiRequest $r,Submission $submission) {
        $this->service->authorize($submission,$r->user(),student:true); $this->service->editable($submission); $this->service->version($submission,$r->integer('data_version'));
        $file=$r->file('file');
        if(file_get_contents($file->getRealPath(),false,null,0,5)!=='%PDF-') throw new ApiException('INVALID_PDF','Isi berkas bukan PDF yang valid.');
        $key=$file->storeAs('submissions/'.$submission->id,Str::uuid().'.pdf','local');
        try {
            $parsed=$this->extractor->extract(Storage::disk('local')->path($key));
            $this->service->locked($submission,$r->integer('data_version'),function($s) use($r,$key,$file,$parsed) {
                $this->service->editable($s);
                $version=1+(int)$s->documents()->where('type',$r->validated('type'))->max('version');
                $old=$s->documents()->where('type',$r->validated('type'))->where('is_current',true)->pluck('id')->all();
                $s->documents()->whereIn('id',$old)->update(['is_current'=>false]); $s->increment('data_version');
                $doc=$s->documents()->create(['type'=>$r->validated('type'),'original_filename'=>Str::limit(basename($file->getClientOriginalName()),200,''),'storage_key'=>$key,'version'=>$version,'data_version'=>$s->data_version,'is_current'=>true,'extraction_status'=>$parsed['status'],'extraction_metadata'=>array_diff_key($parsed,['pages'=>true]),'pages'=>$parsed['pages']]);
                $this->service->audit($s,$r->user(),'document_uploaded',['document_id'=>$doc->id,'document_version'=>$version,'superseded_ids'=>$old,'extraction_status'=>$parsed['status']]);
            });
        } catch(\Throwable $e) { Storage::disk('local')->delete($key); throw $e; }
        return new SubmissionResource($submission->refresh());
    }
    public function destroy(ApiRequest $r,Submission $submission,string $documentId) {
        $this->service->authorize($submission,$r->user(),student:true);
        $this->service->locked($submission,$r->integer('data_version'),function($s) use($r,$documentId) {
            $this->service->editable($s); $d=$s->documents()->findOrFail($documentId);
            if(!$d->is_current) throw new ApiException('DOCUMENT_SUPERSEDED','Dokumen ini sudah digantikan.',409);
            $d->update(['is_current'=>false]); $s->increment('data_version'); $this->service->audit($s,$r->user(),'document_removed',['document_id'=>$d->id]);
        });
        return new SubmissionResource($submission->refresh());
    }
    public function download(ApiRequest $r,Submission $submission,string $documentId) {
        $this->service->authorize($submission,$r->user()); $d=$submission->documents()->findOrFail($documentId);
        return Storage::disk('local')->download($d->storage_key,$d->original_filename,['Content-Type'=>'application/pdf','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
