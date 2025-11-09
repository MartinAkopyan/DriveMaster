<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class ConfirmLesson extends Mutation
{
    protected $attributes = [
        'name' => 'confirmLesson',
        'description' => 'A mutation for confirming lesson'
    ];

    public function type(): Type
    {
        return GraphQL::type('Lesson');
    }

    public function args(): array
    {
        return [
            'lesson_id' => [
                'type' => Type::nonNull(Type::int())
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $instructor = auth()->user();

        if ( !$instructor || !$instructor->isInstructor()) {
            throw new \Exception('Unauthorized: you can not confirm this lesson');
        }

        $lesson = Lesson::findOrFail($args['lesson_id']);

        if ($lesson->status !== LessonStatus::PLANNED) {
            throw new \Exception('Only planned lessons can be confirmed');
        }

        if ($instructor->id !== $lesson->instructor_id) {
            throw new \Exception('Unauthorized: only instructor of this lesson can confirm it');
        }

        $lesson->update([
            'status' => LessonStatus::CONFIRMED
        ]);

        return $lesson;
    }
}
