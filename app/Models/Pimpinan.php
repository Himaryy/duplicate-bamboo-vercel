<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pimpinan extends Model
{
    use HasFactory;

    protected $primaryKey = 'ppid';
    public $incrementing  = false;
    protected $keyType     = 'string';

    protected $fillable = [   // allow these fields only
        'ppid',
        'name',
        'jabatan',
        'deskripsi',
        'image',
    ];
}