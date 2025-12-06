<?php

namespace Tests\Feature\GraphQL\Queries;

use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AvailableSlotsTest extends TestCase
{
    use RefreshDatabase;
    private User $student;
    private User $instructor;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->instructor = User::factory()->create([
            'role' => UserRole::INSTRUCTOR,
            'is_approved' => true
        ]);

        Profile::factory()->create(['user_id' => $this->instructor->id]);

        $this->date = now()->addDays(5)->format('Y-m-d');
    }

    /** @test */
    public function show_all_available_slots_when_no_lessons_booked(): void
    {
        $query = $this->buildQuery($this->instructor->id, $this->date);

        $response = $this->actingAs($this->student)
            ->postJson('/graphql', ['query' => $query]);

        $response->assertOk();
        $this->assertCount(6,$response->json('data.availableSlots'));

        \Log::info($response->json());

        $response->assertJsonStructure([
            'data' => [
                'availableSlots' => [
                    '*' => ['start_time', 'end_time']
                ]
            ]
        ]);
    }

    private function buildQuery(int $instructorId, string $date): string
    {
        return "
            query {
                availableSlots(
                    instructor_id: {$instructorId},
                    date: \"{$date}\"
                ) {
                    start_time
                    end_time
                }
            }
        ";
    }
}
