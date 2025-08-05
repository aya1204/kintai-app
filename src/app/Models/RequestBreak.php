<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestBreak extends Model
{
    use HasFactory;
    protected $fillable = [
        'request_work_id',
        'start_time',
        'end_time',
    ];

    // request(休憩修正申請)と多対1の関係
    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    // requestWork(出勤・退勤時間の修正申請)と多対1の関係
    public function requestWork()
    {
        return $this->belongsTo(RequestWork::class);
    }
}
