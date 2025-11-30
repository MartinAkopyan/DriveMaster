<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\InstructorRegistered;
use App\Events\StudentRegistered;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserRegistrationService
{
    public function registerStudent(array $userData): array
    {
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Student registered', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        event(new StudentRegistered($user, $token));


        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function registerInstructor(array $userData): array
    {
        return DB::transaction(function () use ($userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => UserRole::INSTRUCTOR,
            ]);

            Profile::create([
                'user_id' => $user->id,
                'phone' => $userData['phone'],
                'bio' => $userData['bio'] ?? null,
                'experience_years' => $userData['experience_years'],
                'car_model' => $userData['car_model'] ?? null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Instructor registered', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            DB::afterCommit(function () use ($user, $token) {
                event(new InstructorRegistered($user, $token));
            });

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }
}
