<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InvalidateInstructorCache
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $this->invalidateInstructorCache($event->instructor);
    }

    private function invalidateInstructorCache($instructor): void
    {
        try {
            Cache::tags(['instructors'])->flush();

            Log::debug('Instructor cache invalidated', [
                'instructor_id' => $instructor->id,
                'instructor_email' => $instructor->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate instructor cache', [
                'instructor_id' => $instructor->id,
                'error' => $e->getMessage(),
            ]);

        }
    }
}
