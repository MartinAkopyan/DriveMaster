<?php

namespace App\Listeners;

use App\Events\LessonCancelledBySystem;
use App\Notifications\LessonCancelledNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSystemLessonCancellationNotifications implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Handle the event.
     */
    public function handle(LessonCancelledBySystem $event): void
    {
        $lesson = $event->lesson;
        $reason = $event->reason;

        try {
            $lesson->instructor->notify(new LessonCancelledNotifications($lesson, null, $reason));
            $lesson->student->notify(new LessonCancelledNotifications($lesson, null, $reason));

            Log::info('System cancellation notification sent', [
                'lesson_id' => $lesson->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send system cancellation notifications', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(LessonCancelledBySystem $event, \Throwable $exception): void
    {
        Log::error('SendSystemCancellationNotifications job failed', [
            'lesson_id' => $event->lesson->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
