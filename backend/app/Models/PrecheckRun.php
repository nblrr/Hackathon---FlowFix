<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PrecheckRun extends Model { protected $guarded=['id']; protected $casts=['snapshot'=>'array', 'result'=>'array'];  }
