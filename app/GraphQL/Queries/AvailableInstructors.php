<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class AvailableInstructors extends Query
{
    protected $attributes = [
        'name' => 'availableInstructors',
        'description' => 'A query for available instructors',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [

        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        return User::query()
            ->where('users.role', '=', UserRole::INSTRUCTOR)
            ->where('users.is_approved', '=', true)
            ->select($select)
            ->with($with)
            ->get();
    }
}
