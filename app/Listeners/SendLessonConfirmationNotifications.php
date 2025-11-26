<?php

namespace App\Listeners;

use App\Events\LessonConfirmed;
use App\Notifications\LessonConfirmedNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLessonConfirmationNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LessonConfirmed $event): void
    {
        $lesson = $event->lesson;

        try {
            $lesson->instructor->notify(new LessonConfirmedNotifications($lesson, true));

            $lesson->student->notify(new LessonConfirmedNotifications($lesson, false));

            Log::info('Lesson confirmation notification sent', [
                'lesson_id' => $lesson->id,
                'instructor_id' => $lesson->instructor_id,
                'student_id' => $lesson->student_id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation notifications', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(LessonConfirmed $event, \Throwable $exception): void
    {
        Log::error('SendConfirmationNotifications job failed', [
            'lesson_id' => $event->lesson->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
