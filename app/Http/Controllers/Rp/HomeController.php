<?php

namespace App\Http\Controllers\Rp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = session('oidc_user');
        
        return view('rp.home', [
            'user' => $user,
        ]);
    }
}

