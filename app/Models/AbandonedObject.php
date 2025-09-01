<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedObject extends Model
{
    protected $table = 'objects';

    protected $fillable = [
        'type',
        'address',
        'coords',
        'eri_id',
        'date_abounded',
        'date_revision',
        'borders',
    ];

    protected $casts = [
        'borders' => 'array', // JSON ⇄ array
    ];
}
