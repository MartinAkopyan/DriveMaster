<?php

namespace App\Repositories;

use App\Enums\UserRole;
use Carbon\Carbon;
use App\Models\Lesson;
use App\Enums\LessonStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LessonRepository
{
    public function hasInstructorConflict(int $instructorId, Carbon $startTime, Carbon $endTime): bool
    {
        return Lesson::where('instructor_id', $instructorId)
            ->whereIn('status', [LessonStatus::PLANNED, LessonStatus::CONFIRMED])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })->exists();
    }

    public function getInstructorSchedule(int $instructorId, Carbon $dateFrom, Carbon $dateTo, ?LessonStatus $status = null): Collection
    {
        $statusStr = $status?->value ?? 'all';
        $cacheKey = sprintf(
            'instructor_schedule:%d:%s:%s:%s',
            $instructorId,
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d'),
            $statusStr
        );

        return Cache::tags(['lessons', "lessons:instructor:{$instructorId}"])
            ->remember($cacheKey, 600, function () use ($instructorId, $dateFrom, $dateTo, $status) {
                $query = Lesson::where('instructor_id', $instructorId)
                    ->whereBetween('start_time', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
                    ->with(['instructor', 'student'])
                    ->orderBy('start_time');

                if ($status) {
                    $query->where('status', $status);
                }

                return $query->get();
            });
    }

    public function getAvailableSlots(int $instructorId, Carbon $date): array
    {
        $cacheKey = sprintf('available_slots:%d:%s', $instructorId, $date->format('Y-m-d'));

        return Cache::tags(['lessons', "lessons:instructor:{$instructorId}"])
            ->remember($cacheKey, 300, function () use ($instructorId, $date) {
                $workStart = $date->copy()->setTime(8, 0);
                $workEnd = $date->copy()->setTime(20, 0);
                $slotDuration = 2;

                $occupied = Lesson::where('instructor_id', $instructorId)
                    ->whereDate('start_time', $date)
                    ->whereIn('status', [LessonStatus::PLANNED, LessonStatus::CONFIRMED])
                    ->get(['start_time', 'end_time']);

                $slots = [];
                $current = $workStart->copy();

                while ($current->lt($workEnd)) {
                    $slotStart = $current->copy();
                    $slotEnd = $current->copy()->addHours($slotDuration);

                    $conflict = $occupied->first(function ($lesson) use ($slotStart, $slotEnd) {
                        return $lesson->start_time < $slotEnd && $lesson->end_time > $slotStart;
                    });

                    if (!$conflict && $slotEnd->lte($workEnd)) {
                        $slots[] = [
                            'start_time' => $slotStart->toDateTimeString(),
                            'end_time' => $slotEnd->toDateTimeString(),
                        ];
                    }

                    $current->addHours($slotDuration);
                }

                return $slots;
            });
    }

    public function createLesson($data): Lesson
    {
        $lesson = Lesson::create($data);

        $this->invalidateInstructorCache($lesson->instructor_id);
        $this->invalidateStudentCache($lesson->student_id);

        return $lesson->loadMissing(['instructor', 'student']);
    }

    public function confirmLesson(Lesson $lesson): Lesson
    {
        $lesson->update([
            'status' => LessonStatus::CONFIRMED
        ]);

        $this->invalidateInstructorCache($lesson->instructor_id);
        $this->invalidateStudentCache($lesson->student_id);

        return $lesson;
    }

    public function cancelLesson(Lesson $lesson, int $canceledBy, ?string $reason = null): Lesson
    {
        $lesson->update([
            'status' => LessonStatus::CANCELLED,
            'cancelled_by' => $canceledBy,
            'cancel_reason' => $reason
        ]);

        $this->invalidateInstructorCache($lesson->instructor_id);
        $this->invalidateStudentCache($lesson->student_id);

        return $lesson;
    }

    public function getUpcomingLessons(int $userId, UserRole $role): Collection
    {
        $roleStr = $role === UserRole::INSTRUCTOR ? 'instructor' : 'student';
        $cacheKey = "upcoming_lessons:{$roleStr}:{$userId}";

        return Cache::tags(['lessons', "lessons:{$roleStr}:{$userId}"])
            ->remember($cacheKey, 300, function () use ($userId, $role) {
                $column = $role === UserRole::INSTRUCTOR ? 'instructor_id' : 'student_id';

                return Lesson::where($column, $userId)
                    ->whereIn('status', [LessonStatus::PLANNED, LessonStatus::CONFIRMED])
                    ->where('start_time', '>', now())
                    ->with(['instructor', 'student'])
                    ->orderBy('start_time')
                    ->get();
            });
    }

    public function getExpiredPendingLessons(Carbon $olderThan): Collection
    {
        return Lesson::where('status', LessonStatus::PLANNED)
            ->where('created_at', '<', $olderThan)
            ->where('start_time', '>', now())
            ->with(['instructor', 'student'])
            ->limit(100)
            ->get();
    }


    private function invalidateInstructorCache(int $instructorId): void
    {
        Cache::tags(["lessons:instructor:{$instructorId}"])->flush();
    }

    private function invalidateStudentCache(int $studentId): void
    {
        Cache::tags(["lessons:student:{$studentId}"])->flush();
    }
}
