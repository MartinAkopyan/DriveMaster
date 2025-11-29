<?php

namespace App\Jobs;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Notifications\LessonReminderNotifications;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLessonReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tomorrow = Carbon::tomorrow();

        $lessons = Lesson::where('status', LessonStatus::CONFIRMED)
            ->whereDate('start_time', $tomorrow)
            ->with(['instructor', 'student'])
            ->get();

        $sentCount = 0;

        foreach ($lessons as $lesson) {
            try {
                $lesson->instructor->notify(new LessonReminderNotifications($lesson, true));
                $lesson->student->notify(new LessonReminderNotifications($lesson, false));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send reminder', [
                   'lesson_id' => $lesson->id,
                   'message' => $e->getMessage(),
                ]);
            }
         }

        Log::info('Lesson reminders sent', [
            'date' => $tomorrow->toDateString(),
            'sent_count' => $sentCount,
            'total_lessons' => $lessons->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendLessonReminders job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
