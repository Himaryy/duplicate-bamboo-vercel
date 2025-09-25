<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $primaryKey = 'pid';   // not an array
    public $incrementing  = false;   // pid is NOT auto-increment
    protected $keyType     = 'string';

    protected $fillable = [
        'pid',
        'kode_produk',
        'nama_produk',
        'kategori_id',
        'jumlah_produk',
        'image',
        'image1',
        'image2',
        'image3',
        'image4',
        'berat',
        'harga',
        'deskripsi',
        'tokped',
        'shopee',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}