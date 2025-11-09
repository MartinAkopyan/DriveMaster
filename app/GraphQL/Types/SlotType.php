<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class SlotType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Slot',
        'description' => 'A type for instructor slot'
    ];

    public function fields(): array
    {
        return [
            'start_time' => [
                'type'=> Type::string()
            ],
            'end_time' => [
                'type'=> Type::string()
            ]
        ];
    }
}
