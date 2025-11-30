<?php

namespace App\Listeners;

use App\Events\LessonCancelled;
use App\Events\LessonCancelledBySystem;
use App\Events\LessonConfirmed;
use App\Events\LessonCreated;
use App\Models\Lesson;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateLessonCache
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $this->invalidateLessonCache($event->lesson);
    }

    private function invalidateLessonCache($lesson): void
    {
        try {
            Cache::tags(["lessons:instructor:{$lesson->instructor_id}"])->flush();
            Cache::tags(["lessons:student:{$lesson->student_id}"])->flush();

            Log::debug('Lesson cache invalidated', [
                'lesson' => $lesson->id,
                'instructor_id' => $lesson->instructor_id,
                'student_id' => $lesson->student_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate lesson cache', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);

        }
    }
}
