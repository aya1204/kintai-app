<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * メール認証画面表示
     */
    public function index()
    {
        return view('staff.auth.verify-email');
    }
}
