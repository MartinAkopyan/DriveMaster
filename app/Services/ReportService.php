<?php

namespace App\Services;

use App\Exceptions\UnauthorizedReportAccessException;
use App\Models\User;
use App\Repositories\LessonRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private UserRepository $userRepo,
        private LessonRepository $lessonRepo
    ){}

    /**
     * @throws UnauthorizedReportAccessException
     */
    public function getInstructorsStats(User $admin): array
    {
        $this->ensureIsAdmin($admin);

        return $this->userRepo->getInstructorStats();
    }

    /**
     * @throws UnauthorizedReportAccessException
     */
    public function getTopInstructors(Carbon $dateFrom, Carbon $dateTo, User $admin): Collection
    {
        $this->ensureIsAdmin($admin);

        return $this->userRepo->getTopInstructors($dateFrom, $dateTo);
    }

    /**
     * @throws UnauthorizedReportAccessException
     */
    public function getLessonsStats(Carbon $dateFrom, Carbon $dateTo, User $admin): array
    {
        $this->ensureIsAdmin($admin);

        return $this->lessonRepo->getLessonsStats($dateFrom, $dateTo);
    }

    /**
     * @throws UnauthorizedReportAccessException
     */
    private function ensureIsAdmin(User $admin): void
    {
        if (!$admin->isAdmin()) {
            throw new UnauthorizedReportAccessException('Only admins allowed to view reports');
        }
    }
}
