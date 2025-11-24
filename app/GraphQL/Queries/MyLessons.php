<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class MyLessons extends Query
{
    protected $attributes = [
        'name' => 'myLessons',
        'description' => 'A query for getting user lessons'
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Lesson'));
    }

    public function args(): array
    {
        return [

        ];
    }

    /**
     * @throws \Exception
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $user = auth()->user();

        if ($user->isInstructor()) {
            $query = $user->lessonsAsInstructor();
        } else if ($user->isStudent()) {
            $query = $user->lessonsAsStudent();
        } else {
            throw new \Exception('Unauthorized');
        }

        return $query
            ->select($select)
            ->with($with)
            ->get();
    }
}
