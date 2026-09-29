<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Document extends Model { protected $guarded=['id']; protected $casts=['pages'=>'array', 'extraction_metadata'=>'array', 'is_current'=>'boolean']; protected $hidden = ['storage_key','pages']; }
