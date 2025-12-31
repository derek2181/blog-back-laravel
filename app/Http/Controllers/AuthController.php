<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request, JwtService $jwt)
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['username'])->first();

        $valid = false;
        if ($user) {
            try {
                $valid = Hash::check($credentials['password'], $user->password);
            } catch (\RuntimeException) {
                $valid = false;
            }
        }

        if (!$user || !$valid) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'access_token' => $jwt->generateToken($user->id, $user->role),
        ]);
    }
}
