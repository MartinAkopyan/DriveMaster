<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Lesson;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class InstructorSchedule extends Query
{
    protected $attributes = [
        'name' => 'instructorSchedule',
        'description' => 'A query for a instructor schedule',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Lesson'));
    }

    public function args(): array
    {
        return [
            'instructor_id' => [
                'type' => Type::int(),
            ],
            'date_from' => [
                'type' => Type::string(),
            ],
            'date_to' => [
                'type' => Type::string(),
            ],
            'lesson_status' => [
                'type' => GraphQL::type('LessonStatusEnum'),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        \DB::listen(function ($query) {
            \Log::info('SQL', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });

        $user = auth()->user();
        $instructor_id = $args['instructor_id'] ?? $user->id;

        if ($user->isInstructor() && $user->id !== $instructor_id) {
            throw new \Exception('Unauthorized: instructors can only view their own schedule');
        }

        if ($user->isStudent()) {
            throw new \Exception('Unauthorized: students cannot view instructor schedules');
        }

        if ($user->isAdmin() && !isset($args['instructor_id'])) {
            throw new \Exception('You should provide instructor id to view instructor schedules');
        }

        $query = Lesson::where('instructor_id', $instructor_id)
            ->select($select)
            ->with($with);

        if (isset($args['date_from'])) {
            $query->where('start_time', '>=', Carbon::parse($args['date_from'])->startOfDay());
        }

        if (isset($args['date_to'])) {
            $query->where('start_time', '<=', Carbon::parse($args['date_to'])->endOfDay());
        }

        if (isset($args['lesson_status'])) {
            $query->where('status', $args['lesson_status']);
        }

        return $query->orderBy('start_time')->get();
    }
}
