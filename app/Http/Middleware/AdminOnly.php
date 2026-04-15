<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // إذا لم يكن هناك مستخدم مسجّل دخول
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول كمدير'
            ], 401);
        }

        // إذا لم يكن المستخدم مديرًا
        if (!($user instanceof Admin)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
            ], 403);
        }

        return $next($request);
    }
}