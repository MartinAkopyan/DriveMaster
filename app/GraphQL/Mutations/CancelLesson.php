<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Enums\LessonStatus;
use App\Exceptions\LessonBookingException;
use App\Models\Lesson;
use App\Services\LessonBookingService;
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
            'lesson_id' => Type::nonNull(Type::int()),
            'reason' => Type::string()
        ];
    }


    /**
     * @throws LessonBookingException
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Lesson
    {

        $user = auth()->user();

        return $this->lessonService->cancelLesson($args['lesson_id'], $user, $args['reason'] ?? null);
    }
}
