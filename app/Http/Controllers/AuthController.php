<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,staff,user'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'email_verification_token' => Str::random(60)
        ]);

        return response()->json([
            'message' => 'Registrasi Berhasil, silahkan Verifikasi email Anda',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user = auth('api')->user();

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Emsail Belum Terverifikasi!'
            ], 403);
        }

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => auth('api')->user()
        ]);
    }

    public function refresh()
    {
        try {
            $newToken = auth()->refresh();
            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token invalid or expired'], 401);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Anda telah logout!']);
    }

    public function forgotPassword(Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => __($status)
        ]);
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return response()->json([
            'message' => __($status)
        ]);
    }

    public function verifyEmail(Request $request) {
        $request->validate([
            'token' => 'required'
        ]);

        $user = User::where('email_verification_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Token verifikasi tidak valid!'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json([
            'message' => 'Email berhasil diverifikasi!'
        ]);
    }

    public function resendVerification(Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan!'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email sudah terverifikasi!'
            ]);
        }

        $user->email_verification_token = Str::random(60);
        $user->save();

        return response()->json(['message' => 'Link Verifikasi dikirim ulang']);
    }
}
