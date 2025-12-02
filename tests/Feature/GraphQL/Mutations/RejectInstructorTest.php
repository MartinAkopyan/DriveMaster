<?php

namespace Tests\Feature\GraphQL\Mutations;

use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RejectInstructorTest extends TestCase
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
                             rejectInstructor(
                                instructor_id: {$this->instructor->id},
                                reason: \"Insufficient experience\") {
                                    id
                                    profile {
                                        rejection_reason
                                    }
                                }
                             }";
    }

    /** @test */
    public function admin_can_reject_instructor(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('data.rejectInstructor.id', $this->instructor->id);
        $response->assertJsonPath('data.rejectInstructor.profile.rejection_reason', 'Insufficient experience');
        $this->assertSoftDeleted('users', [
            'id' => $this->instructor->id,
        ]);
    }

    /** @test */
    public function student_cannot_reject_instructor(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Only admins can perform this action');
        $this->assertDatabaseHas('users', [
            'id' => $this->instructor->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function instructor_cannot_reject_instructor(): void
    {
        $anotherInstructor = User::factory()->create(['role' => UserRole::INSTRUCTOR]);
        $response = $this->actingAs($anotherInstructor, 'sanctum')
            ->postJson('graphql', ['query' => $this->query]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Only admins can perform this action');
        $this->assertDatabaseHas('users', [
            'id' => $this->instructor->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function cannot_reject_already_approved_instructor(): void
    {
        $anotherInstructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        $query2 = "mutation {
                             rejectInstructor(
                                instructor_id: {$anotherInstructor->id},
                                reason: \"Insufficient experience\") {
                                    id
                                    profile {
                                        rejection_reason
                                    }
                                }
                             }";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/graphql', ['query' => $query2]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Cannot reject approved instructor');
        $this->assertDatabaseHas('users', [
           'id' => $anotherInstructor->id,
           'deleted_at' => null,
        ]);
    }

    /** @test */
    public function cannot_reject_non_instructor(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);

        $query2 = "mutation { rejectInstructor(
                            instructor_id: {$student->id},
                            reason: \"Insufficient experience\") {
                                id
                                profile {
                                    rejection_reason
                                }}}";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/graphql', ['query' => $query2]);

        $response->assertOk();
        $response->assertJsonPath('errors.0.message', 'Cannot find instructor');
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function guest_cannot_approve_instructor(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => "mutation {
                approveInstructor(instructor_id: {$this->instructor->id}) {
                    id
                }
            }"
        ]);

        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    /** @test */
    public function guest_cannot_reject_instructor(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => "mutation {
                rejectInstructor(instructor_id: {$this->instructor->id}, reason: \"Test\") {
                    id
                }
            }"
        ]);

        $response->assertJsonPath('message', 'Unauthenticated.');
    }
}
