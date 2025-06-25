<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AuthPayloadType extends GraphQLType
{
    protected $attributes = [
        'name' => 'AuthPayload',
        'description' => 'Authentication response with token and user data'
    ];

    public function fields(): array
    {
        return [
            'token' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'JWT access token',
                'selectable' => false,
            ],
            'user' => [
                'type' => Type::nonNull(GraphQL::type('User')),
                'description' => 'Authenticated user date'
            ]
        ];
    }
}
