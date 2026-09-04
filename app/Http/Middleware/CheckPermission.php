<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() || $user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Unauthorized. You do not have permission to perform this action.');
    }
}
