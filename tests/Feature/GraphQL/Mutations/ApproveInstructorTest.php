<?php

namespace Tests\Feature\GraphQL\Mutations;

use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApproveInstructorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $instructor;

    private string $query;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->instructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => false
        ]);

        Profile::factory()->create(['user_id' => $this->instructor->id]);

        $this->query = "mutation {
                            approveInstructor(instructor_id: {$this->instructor->id}) {
                                    id
                                    is_approved
                                }
                            }";
    }

    /** @test */
    public function admin_can_approve_instructor(): void
    {

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('data.approveInstructor.id', $this->instructor->id);
        $response->assertJsonPath('data.approveInstructor.is_approved', true);
        $this->assertDatabaseHas('users', [
            'id' => $this->instructor->id,
            'is_approved' => true
        ]);
    }

    /** @test */
    public function student_cannot_approve_instructor(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::STUDENT
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Only admins can perform this action');
        $this->assertDatabaseHas('users', [
            'id' => $this->instructor->id,
            'is_approved' => false
        ]);
    }

    /** @test */
    public function instructor_cannot_approve_instructor():void
    {
        $anotherInstructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        $response = $this->actingAs($anotherInstructor, 'sanctum')
            ->postJson('/graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Only admins can perform this action');
        $this->assertDatabaseHas('users', [
            'id' => $this->instructor->id,
            'is_approved' => false
        ]);
    }

    /** @test */
    public function cannot_approve_already_approved_instructor(): void
    {
        $this->instructor->update(['is_approved' => true]);
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Instructor is already approved');
        $this->assertDatabaseHas('users', [
            'id' => $this->instructor->id,
            'is_approved' => true
        ]);
    }

}
