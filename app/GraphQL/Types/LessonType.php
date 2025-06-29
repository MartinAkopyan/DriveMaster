<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Lesson;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use App\Models\User;

class LessonType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Lesson',
        'description' => 'A type for a lesson',
        'model' => Lesson::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the lesson',
            ],
            'instructor_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The instructor of the lesson',
            ],
            'student_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The student of the lesson',
            ],
            'start_time' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The start time of the lesson',
            ],
            'end_time' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The end time of the lesson',
            ],
            'status' => [
                'type' => Type::nonNull(GraphQL::type('LessonStatusEnum')),
                'description' => 'The status of the lesson',
            ],
            'notes' => [
                'type' => Type::string(),
                'description' => 'The notes of the lesson',
            ],
            'instructor' => [
                'type' => Type::nonNull(GraphQL::type('User')),
                'description' => 'The instructor of the lesson',
            ],
            'student' => [
                'type' => Type::nonNull(GraphQL::type('User')),
                'description' => 'The student of the lesson',
            ],
            'canBeCancelled' => [
                'type' => Type::boolean(),
                'description' => 'The status of the lesson can be cancelled',
            ],
            'getDurationAttribute' => [
                'type' => Type::int(),
                'description' => 'The duration of the lesson',
            ]
        ];
    }
}
