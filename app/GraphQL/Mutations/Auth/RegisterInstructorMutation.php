<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\UserRegistrationService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class RegisterInstructorMutation extends Mutation
{
    protected $attributes = [
        'name' => 'registerInstructor',
        'description' => 'A mutation for registering an instructor'
    ];

    public function type(): Type
    {
        return GraphQL::type('AuthPayload');
    }

    public function args(): array
    {
        return [
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required', 'email', 'unique:users,email'],
            ]
            ,
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required', 'min:2', 'max:50'],
            ],
            'password' => [
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required', 'min:8', 'max:16','confirmed'],
            ],
            'password_confirmation' => [
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required'],
            ],
            'phone' => [
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required', 'unique:profiles,phone', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            ],
            'bio' => [
                'type' => Type::string(),
                'rules' => ['nullable', 'max:1000'],
            ],
            'experience_years' => [
                'type' => Type::nonNull(Type::int()),
                'rules' => ['required', 'numeric', 'min:1'],
            ],
            'car_model' => [
                'type' => Type::string(),
                'rules' => ['nullable', 'max:50'],
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        try {
            return UserRegistrationService::registerInstructor($args);
        } catch (\Exception $e){
            throw new \GraphQL\Error\Error('Registration failed: '.$e->getMessage());
        }
    }
}
