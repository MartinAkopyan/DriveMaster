<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Enums\LessonStatus;
use App\GraphQL\Enums\LessonStatusEnum;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class CreateLesson extends Mutation
{
    protected $attributes = [
        'name' => 'createLesson',
        'description' => 'A mutation for creating a lesson(only student)'
    ];

    public function type(): Type
    {
        return GraphQL::type('Lesson');
    }

    public function args(): array
    {
        return [
            'instructor_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Id of the instructor of the lesson',
            ],
            'date' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Date of the lesson',
                'rules' => ['required', 'date'],
            ],
            'slot' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The slot of the lesson',
            ],
            'notes' => [
                'type' => Type::string(),
                'description' => 'Notes of the lesson',
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $student = auth()->user();

        if (!$student || !$student->isStudent()) {
            throw new \Exception('Unauthorized: Only students can create lessons.');
        }

        $instructor = User::findOrFail($args['instructor_id']);

        if (!$instructor->isInstructor() || !$instructor->is_approved) {
            throw new \Exception('Instructor should be approved');
        }

        if ($args['slot'] < 1 || $args['slot'] > 6) {
            throw new \Exception('Incorrect slot');
        }

        $baseTime = Carbon::parse($args['date'])->setTime(8, 0);
        $startTime = $baseTime->copy()->addHours(($args['slot'] - 1) * 2);
        $endTime = $startTime->copy()->addHours(2);

        if ($startTime->isPast()) {
            throw new \Exception('Choose correct time for lesson');
        }

        $conflict = Lesson::where('instructor_id', $instructor->id)
            ->whereIn('status', [
                LessonStatus::PLANNED->value,
                LessonStatus::CONFIRMED->value,
            ])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();


        if($conflict) {
            throw new \Exception('This time slot is already booked for the instructor');
        }

        return Lesson::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => LessonStatus::PLANNED,
            'notes' => $args['notes'] ?? NULL,
        ]);
    }
}
