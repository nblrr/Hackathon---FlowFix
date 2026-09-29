<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class SubmissionResource extends JsonResource {
    public function toArray($request): array {
        $s=$this->resource;
        $versioned=fn($items)=>$items->map(fn($item)=>array_merge($item->toArray(),['is_stale'=>$item->data_version!==$s->data_version]));
        return ['id'=>$s->id,'service_type'=>$s->service_type,'status'=>$s->status,'data_version'=>$s->data_version,'sop_version'=>$s->sop_version,'form_data'=>(object)$s->form_data,'updated_at'=>$s->updated_at,
            'documents'=>$s->documents()->orderByDesc('id')->get(), 'messages'=>$s->messages()->orderBy('id')->get(),
            'prechecks'=>$versioned($s->prechecks()->latest('id')->get()), 'reviews'=>$s->reviews()->latest('id')->get(),
            'plans'=>$versioned($s->plans()->latest('id')->get()), 'summaries'=>$versioned($s->summaries()->latest('id')->get()),
            'audit_events'=>$s->auditEvents()->latest('id')->get(),
            'completeness'=>app(\App\Services\SubmissionService::class)->findings($s),
        ];
    }
}
