<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureUserType
{

    public function handle(Request $request, Closure $next, $type)
    {
        $loginAs = auth()->payload()->get('login_as');

        if ($loginAs !== $type) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        return $next($request);
    }
}
