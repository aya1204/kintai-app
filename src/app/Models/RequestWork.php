<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Request as RequestModel;

class RequestWork extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'work_id',
    ];

    // users(スタッフ)と多対1の関係
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // request(修正申請)と多対1の関係
    public function request()
    {
        return $this->belongsTo(RequestModel::class);
    }

    // requestBreaks(休憩時間の修正申請)と1対多の関係
    public function requestBreaks() {
        return $this->hasMany(RequestBreak::class);
    }

    // work（出勤・退勤）と1対1の関係
    public function work()
    {
        return $this->hasOne(RequestModel::class, 'work_id', 'work_id');
    }
}
