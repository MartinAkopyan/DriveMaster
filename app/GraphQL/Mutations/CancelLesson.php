<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Enums\LessonStatus;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class CancelLesson extends Mutation
{
    protected $attributes = [
        'name' => 'cancelLesson',
        'description' => 'Cancel a planned or confirmed lesson (student or instructor)',
    ];

    public function type(): Type
    {
        return GraphQL::type('Lesson');
    }

    public function args(): array
    {
        return [
            'lesson_id' => Type::nonNull(Type::int()),
            'reason' => Type::string()
        ];
    }

    /**
     * @throws \Exception
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {

        $user = auth()->user();
        $lesson = Lesson::findOrFail($args['lesson_id']);

        if ($user->id !== $lesson->instructor_id && $user->id !== $lesson->student_id) {
            throw new \Exception('Unauthorized: You can cancel only your own lessons.');
        }

        if (!in_array($lesson->status, [LessonStatus::CONFIRMED, LessonStatus::PLANNED], true)) {
            throw new \Exception("Lesson with status '{$lesson->status}' can't be cancelled.");
        }

        if ($lesson->start_time->isPast()) {
            throw new \Exception('You can\'t cancel a lesson that has already started');
        }

        if ($user->isStudent() && now()->diffInHours($lesson->start_time, false) < 12) {
            throw new \Exception('Students must cancel the lesson at least 12 hours before the lesson');
        }

        $lesson->update([
           'status' => LessonStatus::CANCELLED,
           'cancelled_by' => $user->id,
           'cancel_reason' => $args['reason'] ?? null,
        ]);

        return $lesson;
    }
}
