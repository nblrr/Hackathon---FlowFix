<?php
namespace App\Services;
use App\Exceptions\ApiException;
use App\Models\{Submission, User};
use App\Http\Requests\ApiRequest;
use Illuminate\Support\Facades\{DB, Validator};
class SubmissionService {
    public function authorize(Submission $s, User $user, bool $student=false, bool $reviewer=false): void {
        if($reviewer && $user->role!=='reviewer') abort(403);
        if($student && ($user->role!=='student' || $s->user_id!==$user->id)) abort(403);
        if($user->role==='student' && $s->user_id!==$user->id) abort(403);
        if($user->role==='reviewer' && $s->status==='DRAFT') abort(403);
    }
    public function editable(Submission $s): void { if(!in_array($s->status,['DRAFT','REVISION_REQUESTED'])) throw new ApiException('SUBMISSION_LOCKED','Pengajuan sedang ditinjau atau sudah diputuskan. Data tidak dapat diubah.',409); }
    public function version(Submission $s, int $version): void { if($s->data_version!==$version) throw new ApiException('STALE_VERSION','Data telah berubah. Muat ulang dan jalankan pemeriksaan atau usulan baru.',409); }
    public function locked(Submission $s, int $version, callable $action): mixed {
        return DB::transaction(function() use($s,$version,$action) { $current=Submission::whereKey($s->id)->lockForUpdate()->firstOrFail(); $this->version($current,$version); return $action($current); });
    }
    public function audit(Submission $s, User $user, string $action, array $details=[]): void { $s->auditEvents()->create(['user_id'=>$user->id,'action'=>$action,'data_version'=>$s->data_version,'details'=>$details]); }
    public function changeFields(Submission $s, array $updates, User $user): void {
        if(array_diff(array_keys($updates),array_keys(config('flowfix.fields')))) throw new ApiException('INVALID_FIELDS','Usulan berisi kolom yang tidak diizinkan.');
        $merged=array_merge($s->form_data,$updates);
        Validator::make($merged,ApiRequest::fieldRules(''))->validate();
        if(!empty($merged['start_date'])&&!empty($merged['end_date'])&&$merged['start_date']>$merged['end_date']) throw new ApiException('INVALID_DATE_RANGE','Tanggal selesai harus sama dengan atau setelah tanggal mulai.');
        $before=$s->form_data;
        if($before===$merged) return;
        $s->update(['form_data'=>$merged,'data_version'=>$s->data_version+1]);
        $this->audit($s,$user,'fields_updated',['before'=>$before,'after'=>$merged]);
    }
    public function snapshot(Submission $s): array {
        return ['submission_id'=>(string)$s->id,'submission_version'=>$s->data_version,'status'=>$s->status,'service_type'=>$s->service_type,'form_data'=>$s->form_data,'sop_version'=>$s->sop_version,'documents'=>$s->documents()->where('is_current',true)->get()->map(fn($d)=>['id'=>$d->id,'filename'=>$d->original_filename,'document_type'=>$d->type,'version'=>$d->version,'extraction_status'=>$d->extraction_status])->all()];
    }
    public function documents(Submission $s, bool $history=false): array {
        return $s->documents()->when(!$history,fn($q)=>$q->where('is_current',true))->get()->map(fn($d)=>['id'=>$d->id,'filename'=>$d->original_filename,'document_type'=>$d->type,'version'=>$d->version,'is_current'=>$d->is_current,'superseded'=>!$d->is_current,'extraction_status'=>$d->extraction_status,'limitations'=>$d->extraction_metadata,'pages'=>$d->pages??[],'content'=>in_array($d->extraction_status,['ready','needs_review']) ? (collect($d->pages)->map(fn($p)=>'[Halaman '.$p['page']."]\n".$p['text'])->implode("\n") ?: '[UNREADABLE]') : '[UNREADABLE]'])->all();
    }
    public function sop(): string { return file_get_contents(config('flowfix.sop_path')); }
    public function findings(Submission $s): array {
        $findings=[]; $human=[]; $docs=$s->documents()->where('is_current',true)->get();
        foreach(config('flowfix.required_fields') as $field) if(!isset($s->form_data[$field])||$s->form_data[$field]==='') $findings[]=$this->finding($field,null,'Isian '.config("flowfix.fields.$field").' belum lengkap.','R02','Lengkapi informasi pengajuan.');
        foreach(config('flowfix.required_documents') as $type) if(!$docs->contains('type',$type)) $findings[]=$this->finding(null,$type,'Lampiran '.config("flowfix.documents.$type").' belum diunggah.','R01','Unggah dokumen yang diperlukan.');
        foreach($docs as $doc) {
            if($doc->extraction_status==='failed') $findings[]=$this->finding(null,$doc->original_filename,$doc->extraction_metadata['message']??'Ekstraksi gagal.','R09','Unggah ulang PDF yang dapat dibuka.');
            elseif($doc->extraction_status!=='ready') $human[]=$this->finding(null,$doc->original_filename,'Sebagian atau seluruh halaman tidak dapat dibaca otomatis.','R09','Periksa PDF secara manual; OCR tidak dijalankan.','uncertain');
        }
        $f=$s->form_data;
        if(!empty($f['start_date'])&&!empty($f['end_date'])&&$f['start_date']>$f['end_date']) $findings[]=$this->finding('end_date',null,'Periode magang tidak valid.','R04','Koreksi tanggal selesai.');
        return ['findings'=>$findings,'human_checks'=>$human];
    }
    private function finding(?string $field, ?string $document, string $issue, string $rule, string $action, string $severity='error'): array { return ['source'=>'deterministic','severity'=>$severity,'field'=>$field,'document'=>$document,'issue'=>$issue,'rule'=>$rule,'evidence'=>null,'suggested_action'=>$action,'page'=>null]; }
    public function submit(Submission $s, int $version, User $user, bool $resubmit, bool $human): void {
        $this->locked($s,$version,function($s) use($user,$resubmit,$human) {
            if($s->status==='SUBMITTED') return; // Retry of an already committed action.
            if($s->status!==($resubmit?'REVISION_REQUESTED':'DRAFT')) throw new ApiException('INVALID_TRANSITION','Pengajuan tidak dapat dikirim dari status ini.',409);
            $checks=$this->findings($s);
            if($checks['findings']) throw new ApiException('INCOMPLETE_SUBMISSION','Lengkapi isian wajib dan lampiran yang dapat dibuka sebelum mengirim.');
            $run=$s->prechecks()->where('data_version',$s->data_version)->where('sop_version',$s->sop_version)->where('status','COMPLETED')->latest('id')->first();
            if($run && $run->result['status']==='NEEDS_FIX') throw new ApiException('PRECHECK_NEEDS_FIX','Perbaiki temuan pasti dan jalankan pemeriksaan ulang sebelum mengirim.');
            if((!$run || $run->result['status']==='NEEDS_HUMAN_CHECK' || $checks['human_checks']) && !$human) throw new ApiException('HUMAN_REVIEW_CONFIRMATION','Analisis belum tuntas. Pilih kirim untuk pemeriksaan manusia secara eksplisit.');
            $s->update(['status'=>'SUBMITTED']); $this->audit($s,$user,$resubmit?'resubmitted':'submitted',['human_review'=>$human]);
            $s->messages()->create(['speaker'=>'system','content'=>'Pengajuan telah dikirim. Keputusan akhir menunggu peninjau.']);
        });
    }
}
