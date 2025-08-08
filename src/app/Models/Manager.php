<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Request as RequestModel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Manager extends Authenticatable
{
    use HasFactory;

    // requests(修正申請)と１対多の関係
    public function requests()
    {
        return $this->hasMany(RequestModel::class, 'manager_id');
    }
}
