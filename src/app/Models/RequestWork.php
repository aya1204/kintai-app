<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestWork extends Model
{
    use HasFactory;

    // users(スタッフ)と多対1の関係
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
