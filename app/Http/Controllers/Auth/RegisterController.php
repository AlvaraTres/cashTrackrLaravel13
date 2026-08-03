<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function index(){
        return view('auth.register');
    }

    public function store(SignUpRequest $request){
        $data = $request->validated();

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
