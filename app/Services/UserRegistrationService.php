<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    /**
     * @throws \Throwable
     */
    public static function registerInstructor(array $userData): array
    {
        return DB::transaction(function () use ($userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => 'instructor',
            ]);

            Profile::create([
                'user_id' => $user->id,
                'phone' => $userData['phone'],
                'bio' => $userData['bio'] ?? null,
                'experience_years' => $userData['experience_years'],
                'car_model' => $userData['car_model'] ?? null,
            ]);

            return [
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ];
        });
    }
}
