<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    #[HeaderParameter(
        'X-XSRF-TOKEN',
        'Value from the XSRF-TOKEN cookie issued by GET /sanctum/csrf-cookie.',
        true,
        type: 'string',
    )]
    #[Response(429, 'Too many login attempts.')]
    public function __invoke(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated())) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are invalid.']]);
        }
        $request->session()->regenerate();

        return UserResource::make($request->user());
    }
}
