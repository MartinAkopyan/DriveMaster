<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\User;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;


class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'description' => 'A type for the User',
        'model' => User::class
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the user'
            ],
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the user'
            ],
            'role' => [
                'type' => Type::nonNull(GraphQL::type('UserRoleEnum')),
                'description' => 'The role of the user'
            ],
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The email of the user'
            ],
            'is_approved' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Approval of instructor'
            ],
            'user_avatar' => [
                'type' => Type::string(),
                'description' => 'The avatar of the user'
            ],
            'profile' => [
                'type' => GraphQL::type('Profile'),
                'description' => 'The profile of the instructor',
                'resolve' => fn(User $user) => $user->profile ?? null
            ],
            'lessonsAsInstructor' => [
                'type' => Type::listOf(GraphQL::type('Lesson')),
                'description' => 'The lessons as instructor',
                'resolve' => function (User $user) {
                    return $user->lessonsAsInstructor;
                }
            ],
            'lessonsAsStudent' => [
                'type' => Type::listOf(GraphQL::type('Lesson')),
                'description' => 'The lessons as student',
                'resolve' => function (User $user) {
                    return $user->lessonsAsStudent;
                }
            ],
            'created_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The date of creation of the user',
                'resolve' => fn(User $user) => $user->created_at->toISOString()

            ],
            'updated_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The date of last update of the user',
                'resolve' => fn(User $user) => $user->updated_at->toISOString()
            ]
        ];
    }
}
