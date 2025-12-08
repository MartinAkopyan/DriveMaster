<?php

namespace Tests\Unit\Enums;

use App\Enums\LessonStatus;
use PHPUnit\Framework\TestCase;

class LessonStatusTest extends TestCase
{
    /** @test */
    public function has_exactly_four_statuses(): void
    {
        $statuses = LessonStatus::cases();

        $this->assertCount(4, $statuses);
    }

    /** @test */
    public function all_status_values_are_correct_and_lowercase(): void
    {
        $this->assertEquals('planned', LessonStatus::PLANNED->value);
        $this->assertEquals('confirmed', LessonStatus::CONFIRMED->value);
        $this->assertEquals('completed', LessonStatus::COMPLETED->value);
        $this->assertEquals('cancelled', LessonStatus::CANCELLED->value);
    }

    /** @test */
    public function can_create_status_from_string(): void
    {
        $planned = LessonStatus::from('planned');
        $confirmed = LessonStatus::from('confirmed');
        $completed = LessonStatus::from('completed');
        $cancelled = LessonStatus::from('cancelled');

        $this->assertEquals(LessonStatus::PLANNED, $planned);
        $this->assertEquals(LessonStatus::CONFIRMED, $confirmed);
        $this->assertEquals(LessonStatus::COMPLETED, $completed);
        $this->assertEquals(LessonStatus::CANCELLED, $cancelled);
    }

    /** @test */
    public function throws_exception_for_invalid_status(): void
    {
        $this->expectException(\ValueError::class);

        LessonStatus::from('pending');
    }
}
