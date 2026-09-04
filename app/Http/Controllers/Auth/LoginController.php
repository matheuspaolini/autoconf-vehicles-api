<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated())) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are invalid.']]);
        }
        $request->session()->regenerate();

        return UserResource::make($request->user());
    }
}
