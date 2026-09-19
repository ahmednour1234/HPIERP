<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * يحرس المسار بصلاحية واحدة أو أكثر.
 *
 * يحل محل أربعة عشر middleware متطابقة كان كل منها يفحص عمودًا منطقيًا
 * واحدًا، ولم تكن تغطي إلا 26 مسارًا من 327.
 *
 *   Route::get(...)->middleware('permission:accounts.view');
 *   Route::post(...)->middleware('permission:accounts.create,accounts.update');
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            return redirect()->route('admin.auth.login');
        }

        // أي صلاحية من المذكورة تكفي، فالمسار الواحد قد يخدم فعلين.
        if ($user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لهذا الإجراء',
            ], 403);
        }

        abort(403, 'ليس لديك صلاحية لهذا الإجراء');
    }
}
