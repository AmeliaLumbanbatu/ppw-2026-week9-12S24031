<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->problemResponse('Invalid Credentials', 'Email atau password salah.', 401, 'invalid-credentials');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]
        ], 'Otentikasi berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user(), 'Profil pemilik token.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Token berhasil dicabut.');
    }
}