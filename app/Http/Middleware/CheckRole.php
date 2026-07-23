<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * الاستخدام في routes: ->middleware('role:admin,hr')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            abort(403, 'الحساب غير مفعّل أو غير مسجل الدخول.');
        }

        if (!empty($roles) && !$user->hasRole($roles)) {
            abort(403, 'ليست لديك صلاحية الوصول لهذه الصفحة.');
        }

        return $next($request);
    }
}
