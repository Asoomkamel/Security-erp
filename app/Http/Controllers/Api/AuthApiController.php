<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /** تسجيل الدخول: يعيد رمز وصول (Bearer Token) عند نجاح المصادقة */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير مفعّل، يرجى مراجعة الإدارة.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'branch_id' => $user->branch_id,
            ],
        ]);
    }

    /** تسجيل الخروج: إبطال رمز الوصول الحالي فقط (وليس كل رموز المستخدم) */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    /** بيانات المستخدم الحالي مع فرعه وملفه كموظف إن وُجد */
    public function me(Request $request)
    {
        $user = $request->user()->load('branch', 'employee');

        return response()->json(['data' => $user]);
    }
}
