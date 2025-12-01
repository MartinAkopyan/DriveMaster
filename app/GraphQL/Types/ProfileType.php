<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Profile;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;



class ProfileType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Profile',
        'description' => 'A type for a instructor profile',
        'model' => Profile::class
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the profile'
            ],
            'user_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the instructor'
            ],
            'phone' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The phone number of the instructor'
            ],
            'bio' => [
                'type' => Type::string(),
                'description' => 'The bio of the instructor'
            ],
            'experience_years' => [
                'type' => Type::int(),
                'description' => 'The experience years of the instructor'
            ],
            'car_model' => [
                'type' => Type::string(),
                'description' => 'The car model of the instructor'
            ],
            'rejection_reason' => [
                'type' => Type::string(),
                'description' => 'The rejection reason for the instructor'
            ],
            'user' => [
                'type' => Type::nonNull(GraphQL::type('User')),
                'description' => 'The user data of the instructor',
                'resolve' => fn($profile) => $profile->user,
            ]
        ];
    }
}
