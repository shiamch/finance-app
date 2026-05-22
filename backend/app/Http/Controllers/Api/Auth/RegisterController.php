<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegisterUserService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /** Register a new user and return their profile. */
    public function __invoke(RegisterRequest $request, RegisterUserService $service): JsonResponse
    {
        $user = $service->register($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
