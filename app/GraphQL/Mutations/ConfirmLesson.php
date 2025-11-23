<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Exceptions\LessonBookingException;
use App\Services\LessonBookingService;
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

    public function __construct(
        private readonly LessonBookingService $lessonService
    ){}

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

    /**
     * @throws LessonBookingException
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $instructor = auth()->user();

        return $this->lessonService->confirmLesson($args['lesson_id'], $instructor);
    }
}
