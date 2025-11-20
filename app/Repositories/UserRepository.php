<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserRepository
{
    public function getApprovedInstructors(): Collection
    {
        return Cache::tags(['instructors'])
            ->remember('approved_instructors', 600, function () {
                return User::where('role', UserRole::INSTRUCTOR)
                    ->where('is_approved', true)
                    ->with('profile')
                    ->orderBy('created_at', 'desc')
                    ->get();
            });
    }

    public function getApprovedInstructor(int $instructorId): ?User
    {
        return Cache::tags(['instructors'])
            ->remember("instructor:{$instructorId}", 600, function () use ($instructorId) {
                return User::where('id', $instructorId)
                    ->where('role', UserRole::INSTRUCTOR)
                    ->where('is_approved', true)
                    ->with('profile')
                    ->first();
            });
    }

    public function getPendingInstructors(): Collection
    {
        return User::where('role', UserRole::INSTRUCTOR)
            ->where('is_approved', false)
            ->with('profile')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function approveInstructor(User $instructor): User
    {
        $instructor->update(['is_approved' => true]);

        $this->invalidateInstructorsCache();

        return $instructor->fresh('profile');

    }

    public function rejectInstructor(User $instructor, ?string $reason = null): void
    {
        if ($reason !== null && $instructor->profile) {
            $instructor->profile->update([
               'rejection_reason' => $reason,
            ]);
        }

        $instructor->delete();
    }

    public function findInstructor(int $instructorId): ?User
    {
        return User::where('id', $instructorId)
            ->where('role', UserRole::INSTRUCTOR)
            ->with('profile')
            ->first();
    }

    private function invalidateInstructorsCache(): void
    {
        Cache::tags(['instructors'])->flush();
    }
}
