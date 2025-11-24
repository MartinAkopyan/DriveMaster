<?php

namespace App\Services;

use App\Enums\LessonStatus;
use App\Exceptions\LessonBookingException;
use App\Models\Lesson;
use App\Repositories\LessonRepository;
use App\Repositories\UserRepository;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LessonBookingService
{
    public function __construct(
        private readonly LessonRepository $lessonRepo,
        private readonly UserRepository $userRepo
    ){}

    /**
     * @throws LessonBookingException
     */
    public function bookLesson(User $student, int $instructorId, string $date, int $slot, ?string $notes = null): Lesson
    {
        $this->ensureUserCanBook($student);

        $instructor = $this->userRepo->getApprovedInstructor($instructorId);

        if (!$instructor) {
            throw new LessonBookingException('Instructor not found or not approved');
        }

        if ($slot < 1 || $slot > 6) {
            throw new LessonBookingException('Invalid slot number. Must be between 1 and 6');
        }

        [$startTime, $endTime] = $this->calculateSlotTime($date, $slot);

        if ($startTime->isPast()) {
            throw new LessonBookingException('Cannot book lessons in the past');
        }

        $lockKey = "lesson:book:{$instructorId}:{$startTime->timestamp}";

        try {
            return Cache::lock($lockKey, 10)->block(5, function () use ($instructor, $student, $startTime, $endTime, $notes) {
                if ($this->lessonRepo->hasInstructorConflict($instructor->id, $startTime, $endTime)) {
                    throw new LessonBookingException('This time slot is already booked');
                }

                return $this->lessonRepo->createLesson([
                    'instructor_id' => $instructor->id,
                    'student_id' => $student->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => $notes
                ]);
            });
        } catch (LockTimeoutException $e) {
            throw new LessonBookingException('Too many simultaneous booking attempts. Please try again in a few seconds.');
        }
    }

    /**
     * @throws LessonBookingException
     */
    public function confirmLesson(int $lessonId, User $instructor): Lesson
    {
        if (!$instructor->isInstructor()) {
            throw new LessonBookingException('Only instructors can confirm lessons');
        }

        $lesson = Lesson::with(['instructor', 'student'])
            ->findOrFail($lessonId);

        if ($lesson->instructor_id !== $instructor->id) {
            throw new LessonBookingException('You can only confirm your own lessons');
        }

        if ($lesson->status !== LessonStatus::PLANNED) {
            throw new LessonBookingException('You can confirm only planned lessons');
        }

        return $this->lessonRepo->confirmLesson($lesson);
    }

    /**
     * @throws LessonBookingException
     */
    public function cancelLesson(int $lessonId, User $user, string $reason = null): Lesson
    {
        $lesson = Lesson::with(['instructor', 'student'])
            ->findOrFail($lessonId);

        $this->ensureUserCanCancel($user, $lesson);

        if (!in_array($lesson->status, [LessonStatus::PLANNED, LessonStatus::CONFIRMED], true)){
            throw new LessonBookingException("Lesson with status '{$lesson->status->value}' cannot be cancelled");
        }

        if ($lesson->start_time->isPast()) {
            throw new LessonBookingException('You cannot cancel a lesson that has already started');
        }

        $this->checkCancellationDeadline($lesson, $user);

        return $this->lessonRepo->cancelLesson($lesson, $user->id, $reason);

    }

    public function cancelLessonAutomatically(Lesson $lesson, string $reason): Lesson
    {
        return $this->lessonRepo->cancelLesson(
            $lesson,
            0, // system user
            $reason
        );
    }

    /**
     * @throws LessonBookingException
     */
    public function getAvailableSlots(int $instructorId, string $date): array
    {
        if (!$this->userRepo->getApprovedInstructor($instructorId)) {
            throw new LessonBookingException('Instructor not found or not approved');
        }

        return $this->lessonRepo->getAvailableSlots($instructorId, Carbon::parse($date));
    }

    /**
     * @throws LessonBookingException
     */
    public function getInstructorSchedule(User $user, ?int $instructorId, Carbon $dateFrom, Carbon $dateTo, ?LessonStatus $status = null): Collection
    {
        if ($user->isInstructor()) {
            if ($instructorId !== null && $instructorId !== $user->id) {
                throw new LessonBookingException('Instructors can only view their own schedule');
            }
            $instructorId = $user->id;
        }

        if ($user->isStudent()) {
            throw new LessonBookingException('Students cannot view instructor schedules');
        }

        if ($instructorId === null && $user->isAdmin()) {
            throw new LessonBookingException('Admin must provide instructor_id to view schedules');
        }

        return $this->lessonRepo->getInstructorSchedule(
            $instructorId,
            $dateFrom,
            $dateTo,
            $status
        );
    }

    /**
     * @throws LessonBookingException
     */
    private function ensureUserCanBook(User $user): void
    {
        if (!$user->isStudent()) {
            throw new LessonBookingException('Only students can book lessons');
        }
    }

    /**
     * @throws LessonBookingException
     */
    private function ensureUserCanCancel(User $user, Lesson $lesson): void
    {
        $isInstructor = $user->id === $lesson->instructor_id;
        $isStudent = $user->id === $lesson->student_id;

        if (!$isInstructor && !$isStudent) {
            throw new LessonBookingException('You can only cancel your own lessons');
        }
    }

    /**
     * @throws LessonBookingException
     */
    private function checkCancellationDeadline(Lesson $lesson, User $user): void
    {
        if ($user->isInstructor()) {
            return;
        }

        $hoursUntilLesson = now()->diffInHours($lesson->start_time, false);

        if ($hoursUntilLesson < 12) {
            throw new LessonBookingException('Students must cancel lesson at least 12 hours in advance');
        }
    }

    private function calculateSlotTime(string $date, int $slot): array
    {
        $baseTime = Carbon::parse($date)->setTime(8, 0);
        $startTime = $baseTime->copy()->addHours(($slot - 1) * 2);
        $endTime = $startTime->copy()->addHours(2);

        return [$startTime, $endTime];
    }
}
