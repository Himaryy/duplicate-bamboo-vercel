<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;
    protected $fillable = ['ip_address', 'visited_at'];
    public static function countToday()
    {
        // asdasd
        return self::whereDate('visited_at', Carbon::today())->count();
    }

    // Hitung pengunjung minggu ini
    public static function countThisWeek()
    {
        return self::whereBetween('visited_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
    }

    // Hitung pengunjung bulan ini
    public static function countThisMonth()
    {
        return self::whereMonth('visited_at', Carbon::now()->month)->count();
    }
}
