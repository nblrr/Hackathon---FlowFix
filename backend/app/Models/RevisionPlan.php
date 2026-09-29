<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RevisionPlan extends Model { protected $guarded=['id']; protected $casts=['snapshot'=>'array', 'proposal'=>'array', 'selected_fields'=>'array'];  }
