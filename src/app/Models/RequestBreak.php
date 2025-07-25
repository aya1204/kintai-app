<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestBreak extends Model
{
    use HasFactory;

    // break(休憩)と１対１の関係
    public function break()
    {
        return $this->belongsTo(BreakTime::class, 'break_id');
    }
}
