<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    use HasFactory;

    // user(スタッフ)と多対1の関係
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // breaks(休憩)と１対多の関係
    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'break_id');
    }

    // requests(修正申請)テーブルと多対1の関係
    public function request()
    {
        return $this->hasOne(Request::class, 'work_id');
    }
}
