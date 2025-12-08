<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    /** @test */
    public function has_exactly_three_roles(): void
    {
        $roles = UserRole::cases();

        $this->assertCount(3, $roles);
    }

    /** @test */
    public function all_role_values_are_correct_and_lowercase(): void
    {
        $this->assertEquals('admin', UserRole::ADMIN->value);
        $this->assertEquals('instructor', UserRole::INSTRUCTOR->value);
        $this->assertEquals('student', UserRole::STUDENT->value);
    }

    /** @test */
    public function can_create_role_from_string(): void
    {
        $admin = UserRole::from('admin');
        $instructor = UserRole::from('instructor');
        $student = UserRole::from('student');

        $this->assertEquals(UserRole::ADMIN, $admin);
        $this->assertEquals(UserRole::INSTRUCTOR, $instructor);
        $this->assertEquals(UserRole::STUDENT, $student);
    }

    /** @test */
    public function throws_exception_for_invalid_role(): void
    {
        $this->expectException(\ValueError::class);

        UserRole::from('superadmin');
    }
}
