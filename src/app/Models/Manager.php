<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Request as RequestModel;

class Manager extends Model
{
    use HasFactory;

    // requests(修正申請)と１対多の関係
    public function requests()
    {
        return $this->hasMany(RequestModel::class, 'manager_id');
    }
}
