<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Download extends Model
{
    
    protected $fillable = [
        'path', 'valid_unti',
    ];
}