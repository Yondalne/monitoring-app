<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'data',
        'date'
    ];

    public function application() {
        return $this->belongsTo(Application::class, 'application_id');
    }
}
