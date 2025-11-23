<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Enums\LessonStatus;
use App\Repositories\LessonRepository;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class InstructorSchedule extends Query
{
    protected $attributes = [
        'name' => 'instructorSchedule',
        'description' => 'A query for a instructor schedule',
    ];

    public function __construct(
        protected readonly LessonRepository $lessonRepo
    ){}

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Lesson'));
    }

    public function args(): array
    {
        return [
            'instructor_id' => [
                'type' => Type::int(),
                'description' => 'Instructor ID (optional for instructors, required for admins if they are not instructors)',
            ],
            'date_from' => [
                'type' => Type::string(),
                'description' => 'Filter lessons from this date (Y-m-d format)',
            ],
            'date_to' => [
                'type' => Type::string(),
                'description' => 'Filter lessons until this date (Y-m-d format)',
            ],
            'lesson_status' => [
                'type' => GraphQL::type('LessonStatusEnum'),
                'description' => 'Filter by lesson status',
            ]
        ];
    }

    /**
     * @throws \Exception
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection
    {
        $user = auth()->user();
        $instructorId = $args['instructor_id'] ?? null;

        if ($user->isInstructor()) {
            if ($instructorId !== null && $instructorId !== $user->id) {
                throw new \Exception('Unauthorized: instructors can only view their own schedule');
            }
            $instructorId = $user->id;
        }

        if ($user->isStudent()) {
            throw new \Exception('Unauthorized: students cannot view instructor schedules');
        }

        if ($user->isAdmin() && $instructorId === null) {
            throw new \Exception('Admin must provide instructor_id to view schedules');
        }

        // Даты
        $dateFrom = isset($args['date_from'])
            ? Carbon::parse($args['date_from'])
            : Carbon::now();

        $dateTo = isset($args['date_to'])
            ? Carbon::parse($args['date_to'])
            : Carbon::now()->addMonth();

        $status = null;
        if (isset($args['lesson_status'])) {
            $status = LessonStatus::from($args['lesson_status']);
        }

        return $this->lessonRepo->getInstructorSchedule(
            $instructorId,
            $dateFrom,
            $dateTo,
            $status
        );
    }
}
