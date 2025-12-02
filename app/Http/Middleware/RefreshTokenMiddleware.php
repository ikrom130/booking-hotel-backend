<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

class RefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            try {
                // Refresh token jika expired
                $newToken = JWTAuth::refresh(JWTAuth::getToken());
                // Set token baru
                auth()->setToken($newToken);
                $response = $next($request);
                $response->headers->set('Authorization', 'Bearer ' . $newToken);
                return $response;
            } catch (JWTException $e) {
                return response()->json(['message' => 'Token invalid'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token missing/invalid'], 401);
        }

        // Token masih valid
        $response = $next($request);
        return $response;
    }
}
