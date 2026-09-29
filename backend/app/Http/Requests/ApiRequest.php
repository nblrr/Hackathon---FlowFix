<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ApiRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public static function fieldRules(string $prefix = 'form_data.'): array {
        $rules=[];
        foreach(array_keys(config('flowfix.fields')) as $key) $rules[$prefix.$key]=['sometimes','nullable','string','max:200'];
        $rules[$prefix.'semester']=['sometimes','nullable','integer','between:1,20'];
        $rules[$prefix.'academic_year']=['sometimes','nullable','regex:/^20\d{2}\/20\d{2}$/'];
        foreach(['start_date','end_date','submission_date'] as $key) $rules[$prefix.$key]=['sometimes','nullable','date_format:Y-m-d'];
        return $rules;
    }
    public function rules(): array {
        $version=['required','integer','min:1'];
        return match($this->route()?->getName()) {
            'login'=>['email'=>['required','email'],'password'=>['required','string','max:200']],
            'submissions.store'=>['service_type'=>['sometimes',Rule::in(['internship'])]],
            'submissions.update'=>['data_version'=>$version,'form_data'=>['required','array:'.implode(',',array_keys(config('flowfix.fields')))]]+self::fieldRules(),
            'messages'=>['message'=>['required','string','max:4000'],'data_version'=>$version],
            'documents.store'=>['file'=>['required','file','min:1','mimetypes:application/pdf','max:'.config('flowfix.max_upload_kb')],'type'=>['required',Rule::in(array_keys(config('flowfix.documents')))],'data_version'=>$version],
            'documents.destroy','precheck','reviewer-summary','revision-plan'=>['data_version'=>$version],
            'submit','resubmit'=>['data_version'=>$version,'human_review'=>['sometimes','boolean']],
            'review'=>['data_version'=>$version,'decision'=>['required',Rule::in(['REVISION_REQUESTED','APPROVED','REJECTED'])],'comment'=>['required','string','min:10','max:4000']],
            'plans.apply'=>['data_version'=>$version,'selected_fields'=>['present','array'],'selected_fields.*'=>['string','distinct',Rule::in(array_keys(config('flowfix.fields')))]],
            default=>[],
        };
    }
    public function messages(): array { return ['required'=>'Kolom :attribute wajib diisi.','date_format'=>'Gunakan tanggal yang valid (YYYY-MM-DD).','mimetypes'=>'Unggah berkas PDF asli.']; }
}
