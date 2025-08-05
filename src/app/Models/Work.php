<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BreakTime;
use App\Models\Request as RequestModel;

class Work extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time'
    ];

    // user(スタッフ)と多対1の関係
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // breaks(休憩)と１対多の関係
    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'work_id');
    }

    // requests(修正申請)テーブルと1対1の関係
    public function request()
    {
        return $this->hasOne(RequestModel::class, 'work_id');
    }
}
