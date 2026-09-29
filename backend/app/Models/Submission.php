<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Submission extends Model {
    protected $guarded = ['id'];
    protected $attributes = ['status'=>'DRAFT','data_version'=>1,'service_type'=>'internship'];
    protected $casts = ['form_data'=>'array','user_id'=>'integer','data_version'=>'integer'];
    public function documents() { return $this->hasMany(Document::class); }
    public function messages() { return $this->hasMany(Message::class); }
    public function prechecks() { return $this->hasMany(PrecheckRun::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function plans() { return $this->hasMany(RevisionPlan::class); }
    public function summaries() { return $this->hasMany(ReviewerSummary::class); }
    public function auditEvents() { return $this->hasMany(AuditEvent::class); }
}
