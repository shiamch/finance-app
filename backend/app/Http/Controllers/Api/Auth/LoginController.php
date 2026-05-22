<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /** Log in an existing user and return their profile. */
    public function __invoke(LoginRequest $request, LoginUserService $service): JsonResponse
    {
        $user = $service->login($request->validated(), $request);

        return (new UserResource($user))->response();
    }
}
