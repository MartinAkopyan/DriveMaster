<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function isAdmin_returns_true_only_for_admin_role(): void
    {
        $admin = new User(['role' => UserRole::ADMIN]);
        $instructor = new User(['role' => UserRole::INSTRUCTOR]);
        $student = new User(['role' => UserRole::STUDENT]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($instructor->isAdmin());
        $this->assertFalse($student->isAdmin());
    }

    /** @test */
    public function isInstructor_returns_true_only_for_instructor_role(): void
    {
        $admin = new User(['role' => UserRole::ADMIN]);
        $instructor = new User(['role' => UserRole::INSTRUCTOR]);
        $student = new User(['role' => UserRole::STUDENT]);

        $this->assertFalse($admin->isInstructor());
        $this->assertTrue($instructor->isInstructor());
        $this->assertFalse($student->isInstructor());
    }

    /** @test */
    public function isStudent_returns_true_only_for_student_role(): void
    {
        $admin = new User(['role' => UserRole::ADMIN]);
        $instructor = new User(['role' => UserRole::INSTRUCTOR]);
        $student = new User(['role' => UserRole::STUDENT]);

        $this->assertFalse($admin->isStudent());
        $this->assertFalse($instructor->isStudent());
        $this->assertTrue($student->isStudent());
    }
}
