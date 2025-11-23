<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Exceptions\LessonBookingException;
use App\Models\Lesson;
use App\Services\LessonBookingService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class BookLesson extends Mutation
{
    protected $attributes = [
        'name' => 'bookLesson',
        'description' => 'A mutation for creating a lesson(only student)'
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

    /**
     * @throws LessonBookingException
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Lesson
    {
        $student = auth()->user();

        return $this->lessonService->bookLesson($student, $args['instructor_id'], $args['date'], $args['slot'], $args['notes']);
    }
}
