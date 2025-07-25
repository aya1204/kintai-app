<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

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
}
