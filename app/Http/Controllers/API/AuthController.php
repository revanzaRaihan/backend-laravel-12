<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User Berhasil Mendaftar',
            'data' => [
                'user' => $user,
                'token' => $this->respondWithToken($token)
            ]
        ], 201);
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email|max:255|filled',
            'password' => 'required|string|min:6|filled'
        ]);

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);

            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token gagal dibuat',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil login',
            'data' => $this->respondWithToken($token)
        ]);
    }

    public function logout()
    {
        try {
            JWTAuth::Invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil logout'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal logout, token tidak valid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refresh(){
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
            'status' => 'success',
            'message' => 'Token telah diperbarui',
            'data' => $this->respondWithToken($newToken)
        ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui token',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function me(){
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return response()->json([
                'status' => 'error',
                'message' => 'User profile',
                'data' => $user
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau expired token',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function respondWithToken($token){
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expired_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }
}

