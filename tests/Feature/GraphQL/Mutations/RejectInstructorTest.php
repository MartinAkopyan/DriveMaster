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

        \Log::info($response->json());
        $response->assertOk();
        $response->assertJsonPath('data.rejectInstructor.id', $this->instructor->id);
        $response->assertJsonPath('data.rejectInstructor.profile.rejection_reason', 'Insufficient experience');
        $this->assertSoftDeleted('users', [
            'id' => $this->instructor->id,
        ]);

    }
}
