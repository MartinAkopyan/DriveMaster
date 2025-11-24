<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Enums\LessonStatus;
use App\Services\LessonBookingService;
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
        protected readonly LessonBookingService $lessonService
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

        return $this->lessonService->getInstructorSchedule(
            $user,
            $instructorId,
            $dateFrom,
            $dateTo,
            $status
        );
    }
}
