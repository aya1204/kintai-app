<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_id',
        'request_work_id',
        'manager_id',
        'approved',
        'staff_remarks',
        'admin_remarks',
    ];

    // managers(管理者)と多対1の関係
    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id');
    }

    // works(出勤・退勤)と０または１対１
    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    // requestWorks(出勤・退勤の修正申請)と多対1の関係
    public function requestWork()
    {
        return $this->belongsTo(RequestWork::class, 'request_work_id');
    }

    // requestBreaks(休憩の修正申請)と1対多の関係
    public function requestBreaks()
    {
        return $this->hasMany(RequestBreak::class);
    }
}
