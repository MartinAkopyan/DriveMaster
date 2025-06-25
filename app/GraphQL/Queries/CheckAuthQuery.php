<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Auth;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class CheckAuthQuery extends Query
{
    protected $attributes = [
        'name' => 'checkAuth',
        'description' => 'A query for checking auth',
    ];

    public function type(): Type
    {
        return Type::boolean();
    }

    public function args(): array
    {
        return [

        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {

        if ($token = request()->bearerToken()) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
            Auth::setUser($user);
        }

        return Auth::check();
    }
}
