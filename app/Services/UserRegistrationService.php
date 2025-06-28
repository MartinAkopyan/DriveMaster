<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRegistrationService
{
    public static function registerStudent(array $userData): array
    {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
            ]);

            return [
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ];
    }
}
