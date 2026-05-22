<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class MeController extends Controller
{
    /** Return the currently authenticated user's profile. */
    public function __invoke(): UserResource
    {
        return new UserResource(request()->user());
    }
}
