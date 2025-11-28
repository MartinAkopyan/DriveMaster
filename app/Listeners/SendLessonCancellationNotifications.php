<?php

namespace App\Listeners;

use App\Events\LessonCancelled;
use App\Models\User;
use App\Notifications\LessonCancelledNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLessonCancellationNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LessonCancelled $event): void
    {
        $lesson = $event->lesson;
        $cancelledBy = $event->cancelledBy ? User::find($event->cancelledBy) : null;
        $reason = $event->reason;

        try {
            $lesson->instructor->notify(new LessonCancelledNotifications($lesson, $cancelledBy, $reason));
            $lesson->student->notify(new LessonCancelledNotifications($lesson, $cancelledBy, $reason));

            Log::info('Lesson cancellation notification sent', [
                'lesson_id' => $lesson->id,
                'cancelled_by' => $cancelledBy?->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notifications', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(LessonCancelled $event, \Throwable $exception): void
    {
        Log::error('SendCancellationNotifications job failed', [
            'lesson_id' => $event->lesson->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
