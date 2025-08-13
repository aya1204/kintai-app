<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;
    protected $table = 'breaks'; // breaksテーブルを使用していると明示
    protected $fillable = [
        'work_id',
        'start_time',
        'end_time'
    ];

    // work(出勤・退勤)と多対1の関係
    public function work() {
        return $this->belongsTo(Work::class, 'work_id');
    }
}
