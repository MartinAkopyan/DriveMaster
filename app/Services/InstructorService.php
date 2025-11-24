<?php

namespace App\Services;

use App\Exceptions\InstructorApprovalException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InstructorService
{
    public function __construct(
        private readonly UserRepository $userRepo
    ){}

    /**
     * @throws InstructorApprovalException
     */
    public function approveInstructor(int $instructorId, User $admin): User
    {
        if (!$admin->isAdmin()) {
            throw new InstructorApprovalException('Only admins can approve instructor');
        }

        $instructor = $this->userRepo->findInstructor($instructorId);

        if (!$instructor) {
            throw new InstructorApprovalException('Instructor not found');
        }

        if ($instructor->is_approved) {
            throw new InstructorApprovalException('Instructor is already approved');
        }

        $instructor = $this->userRepo->approveInstructor($instructor);

        Log::info('Instructor approved', [
            'instructor_id' => $instructor->id,
            'instructor_email' => $instructor->email,
            'approved_by' => $admin->id
        ]);

        // TODO event(new InstructorApproved($instructor));

        return $instructor;
    }

    /**
     * @throws InstructorApprovalException
     */
    public function rejectInstructor(int $instructorId, User $admin, ?string $reason = null): User
    {
        if (!$admin->isAdmin()) {
            throw new InstructorApprovalException('Only admins can reject instructor');
        }

        $instructor = $this->userRepo->findInstructor($instructorId);

        if (!$instructor) {
            throw new InstructorApprovalException('Cannot find instructor');
        }

        if ($instructor->is_approved) {
            throw new InstructorApprovalException('Cannot reject approved instructor');
        }

        $instructor = $this->userRepo->rejectInstructor($instructor, $reason);

        Log::info('Instructor rejected', [
            'instructor_id' => $instructor->id,
            'instructor_email' => $instructor->email,
            'rejected_by' => $admin->id,
            'reason' => $reason,
        ]);

        // TODO event(new InstructorRejected($instructor, $reason));

        return $instructor;
    }

    public function getAvailableInstructors(): Collection
    {
        return $this->userRepo->getApprovedInstructors();
    }

    /**
     * @throws \Exception
     */
    public function getPendingInstructors(User $admin): Collection
    {
        if (!$admin->isAdmin()) {
            throw new \Exception('Only admins can view this data');
        }

        return $this->userRepo->getPendingInstructors($admin);
    }

    /**
     * @throws \Exception
     */
    public function getInstructorsStats(User $admin): array
    {
        if (!$admin->isAdmin()) {
            throw new \Exception('Only admins can view this data');
        }

        return $this->userRepo->getInstructorStats();
    }
}
