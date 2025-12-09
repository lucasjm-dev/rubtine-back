<?php

namespace App\Domains\Users\Middleware;

use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureCompanyUser
{

    public function handle(Request $request, Closure $next)
    {
        $payload = auth()->payload();
        $loginAs = $payload->get('login_as');

        if ($loginAs !== User::TYPE_COMPANY) {
            return ApiResponse::error('access_denied', null, 403);
        }

        return $next($request);
    }
}
