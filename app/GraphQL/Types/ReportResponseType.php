<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ReportResponseType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ReportResponse',
        'description' => 'A type for report response',
    ];

    public function fields(): array
    {
        return [
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Status message'
            ],
            'estimated_time' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Estimated time to complete'
            ],
            'report_type' => [
              'type' => Type::nonNull(Type::string()),
              'description' => 'Report type'
            ],
            'date_from' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Start date'
            ],
            'date_to' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'End date'
            ]
        ];
    }
}
