<?php

namespace Tests\Unit\Jobs;

use App\Enums\LessonStatus;
use App\Jobs\SendLessonRemindersJob;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\LessonReminderNotifications;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class SendLessonRemindersJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function send_lesson_reminders_job_has_correct_configuration(): void
    {
        $job = new SendLessonRemindersJob();

        $this->assertEquals(1, $job->tries);
        $this->assertEquals(300, $job->timeout);
    }

    /** @test
     * @throws \Exception
     */
    public function send_lesson_reminders_job_sends_for_tomorrow_lessons(): void
    {
        Notification::fake();

        $instructor = User::factory()->instructor()->approved()->create();
        $student = User::factory()->student()->create();

        $tomorrowLesson = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::CONFIRMED,
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
            'end_time' => Carbon::tomorrow()->setTime(12, 0),
        ]);

        $laterLesson = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::CONFIRMED,
            'start_time' => Carbon::tomorrow()->addDay()->setTime(10, 0),
            'end_time' => Carbon::tomorrow()->addDay()->setTime(12, 0),
        ]);

        $todayLesson = Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::CONFIRMED,
            'start_time' => Carbon::today()->setTime(14, 0),
            'end_time' => Carbon::today()->setTime(16, 0),
        ]);

        Log::shouldReceive('error')->never();
        Log::shouldReceive('info')->once();

        $job = new SendLessonRemindersJob();

        $job->handle();


        Notification::assertSentTo($instructor, LessonReminderNotifications::class);
        Notification::assertSentTo($student, LessonReminderNotifications::class);

        Notification::assertCount(2);
    }

    /** @test */
    public function send_lesson_reminders_job_skips_non_confirmed_lessons(): void
    {
        Notification::fake();

        $instructor = User::factory()->instructor()->approved()->create();
        $student = User::factory()->student()->create();

        Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::PLANNED,
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
            'end_time' => Carbon::tomorrow()->setTime(12, 0),
        ]);

        Lesson::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => LessonStatus::CANCELLED,
            'start_time' => Carbon::tomorrow()->setTime(14, 0),
            'end_time' => Carbon::tomorrow()->setTime(16, 0),
        ]);

        Log::shouldReceive('info')->once();

        $job = new SendLessonRemindersJob();

        $job->handle();

        Notification::assertNothingSent();
    }
}
