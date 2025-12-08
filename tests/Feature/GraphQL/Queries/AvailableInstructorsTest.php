<?php

namespace Tests\Feature\GraphQL\Queries;

use App\Enums\UserRole;
use App\Models\Profile;
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

    /** @test */
    public function does_not_show_soft_deleted_instructors(): void
    {
        $approvedInstructor = User::factory()
            ->instructor()
            ->approved()
            ->create();

        $rejectedInstructor = User::factory()
            ->instructor()
            ->create();

        $rejectedInstructor->delete();

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $instructors = $response->json('data.availableInstructors');

        $this->assertCount(1, $instructors);

        $instructorIds = collect($instructors)->pluck('id')->toArray();
        $this->assertContains($approvedInstructor->id, $instructorIds);
    }

    /** @test */
    public function does_not_show_students_or_admins(): void
    {
        $instructor = User::factory()
            ->instructor()
            ->approved()
            ->create();

        $student = User::factory()
            ->student()
            ->create();

        $admin = User::factory()
            ->admin()
            ->create();

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $instructors = $response->json('data.availableInstructors');

        $this->assertCount(1, $instructors);

        $this->assertEquals($instructor->id, $instructors[0]['id']);
        $this->assertEquals('instructor', $instructors[0]['role']);
    }

    /** @test */
    public function returns_empty_array_when_no_approved_instructors(): void
    {
        User::factory()->count(3)
            ->instructor()
            ->create();

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $this->assertCount(0, $response->json('data.availableInstructors'));
        $this->assertEquals([], $response->json('data.availableInstructors'));
    }

    /** @test */
    public function guest_cannot_view_available_instructors(): void
    {
        $query = $this->buildQuery();

        $response = $this->postJson('/graphql', ['query' => $query]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    /** @test */
    public function instructors_have_required_profile_data(): void
    {
        $instructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        Profile::factory()->create([
            'user_id' => $instructor->id,
            'phone' => '+48123456789',
            'bio' => 'Experienced instructor',
            'experience_years' => 10,
            'car_model' => 'Toyota Corolla'
        ]);

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();
        $instructorData = $response->json('data.availableInstructors.0');

        $this->assertNotNull($instructorData['profile']);
        $this->assertEquals('+48123456789', $instructorData['profile']['phone']);
        $this->assertEquals('Experienced instructor', $instructorData['profile']['bio']);
        $this->assertEquals(10, $instructorData['profile']['experience_years']);
        $this->assertEquals('Toyota Corolla', $instructorData['profile']['car_model']);
    }

    /** @test */
    public function returns_instructors_ordered_by_newest_first(): void
    {
        $oldInstructor = User::factory()
            ->instructor()
            ->approved()
            ->create(['created_at' => now()->subDays(10)]);

        $middleInstructor = User::factory()
            ->instructor()
            ->approved()
            ->create(['created_at' => now()->subDays(5)]);

        $newInstructor = User::factory()
            ->instructor()
            ->approved()
            ->create(['created_at' => now()]);

        $query = $this->buildQuery();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();

        $instructors = $response->json('data.availableInstructors');

        $this->assertEquals($newInstructor->id, $instructors[0]['id']);
        $this->assertEquals($middleInstructor->id, $instructors[1]['id']);
        $this->assertEquals($oldInstructor->id, $instructors[2]['id']);
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
