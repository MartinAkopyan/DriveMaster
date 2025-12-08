<?php

namespace Tests\Feature\GraphQL\Queries;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AvailableInstructorsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()
            ->student()
            ->create();
    }

    /** @test */
    public function shows_only_approved_instructors(): void
    {
        User::factory()
            ->count(3)
            ->instructor()
            ->approved()
            ->create();

        User::factory()
            ->count(2)
            ->instructor()
            ->create(['is_approved' => false]);

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $instructors = $response->json('data.availableInstructors');
        $this->assertCount(3, $instructors);

        $response->assertJsonStructure([
            'data' => [
                'availableInstructors' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'is_approved',
                        'profile' => [
                            'phone',
                            'bio',
                            'experience_years',
                            'car_model'
                        ]
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function does_not_show_unapproved_instructors(): void
    {
        $approvedInstructor = User::factory()
            ->instructor()
            ->approved()
            ->create();

        $unapprovedInstructors = User::factory()
            ->count(3)
            ->instructor()
            ->create();

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $instructors = $response->json('data.availableInstructors');

        $this->assertCount(1, $instructors);
        $instructorIds = collect($instructors)->pluck('id')->toArray();

        $this->assertContains($approvedInstructor->id, $instructorIds);

        foreach ($unapprovedInstructors as $instructor) {
            $this->assertNotContains($instructor->id, $instructorIds);
        }
    }

    private function buildQuery(): string
    {
        return "
            query {
                availableInstructors {
                    id
                    name
                    email
                    role
                    is_approved
                    profile {
                        phone
                        bio
                        experience_years
                        car_model
                    }
                }
            }
        ";
    }
}
