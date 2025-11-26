<?php

namespace App\Listeners;

use App\Events\LessonCreated;
use App\Notifications\LessonCreatedNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLessonCreationNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LessonCreated $event): void
    {
        $lesson = $event->lesson;

        try {
            $lesson->instructor->notify(new LessonCreatedNotifications($lesson, true));

            $lesson->student->notify(new LessonCreatedNotifications($lesson, false));

            Log::info('Lesson notification sent.', [
                'lesson_id' => $lesson->id,
                'instructor_id' => $lesson->instructor_id,
                'student_id' => $lesson->student_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send lesson notification', [
               'lesson_id' => $lesson->id,
               'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(LessonCreated $event, \Throwable $exception): void
    {
        Log::error('SendLessonNotification job failed', [
           'lesson_id' => $event->lesson->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
