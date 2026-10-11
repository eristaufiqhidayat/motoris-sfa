<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }
        if ($user->role !== 'sales') {
            throw ValidationException::withMessages(['email' => 'Akun ini bukan akun sales lapangan.']);
        }

        return [
            'token' => $user->createToken($data['device'] ?? 'oasis-sales')->plainTextToken,
            'user' => $this->userPayload($user),
        ];
    }

    public function me(Request $request)
    {
        return ['user' => $this->userPayload($request->user())];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function userPayload(User $user): array
    {
        $route = $user->salesRoute;

        return [
            'id' => $user->id,
            'nama' => $user->name,
            'kode' => $user->kode,
            'depot' => $user->depot?->nama,
            'rute' => $route ? ['kode' => $route->kode, 'nama' => $route->nama, 'kendaraan' => $route->vehicle?->nama] : null,
        ];
    }
}
