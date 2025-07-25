<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    // work(出勤・退勤)と多対1の関係
    public function work()
    {
        return $this->belongsTo(Work::class, 'work_id');
    }
}
