<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'required|string',
            'password_hash' => 'required|string|min:6',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password_hash' => Hash::make($request->password_hash),
            'role' => 'customer', // default role
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'user' => $user
        ], 210);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password_hash' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Menggunakan password_hash sesuai nama kolom di database Anda
        if (!$user || !Hash::check($request->password_hash, $user->password_hash)) {
            return response()->json(['message' => 'Kredensial salah'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user
        ]);
    }
    public function getProfile(Request $request)
    {
        return response()->json([
            'message' => 'Profil berhasil diambil',
            'user' => $request->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'sometimes|string',
            'password_hash' => 'nullable|string|min:6',
        ]);

        if ($request->has('full_name')) $user->full_name = $request->full_name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('phone')) $user->phone = $request->phone;
        if ($request->filled('password_hash')) {
            $user->password_hash = Hash::make($request->password_hash);
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user
        ]);
    }
}
