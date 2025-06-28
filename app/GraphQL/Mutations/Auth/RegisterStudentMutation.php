<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\User;
use App\Services\UserRegistrationService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Hash;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class RegisterStudentMutation extends Mutation
{
    protected $attributes = [
        'name' => 'registerStudent',
        'description' => 'A mutation for registering a student.'
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
            ]
        ];
    }

    public function validationErrorMessages(array $args = []): array
    {
        return [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.exists' => 'Sorry, this email address is already in use',
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {

        try {
            return UserRegistrationService::registerStudent($args);
        } catch(\Exception $e) {
            throw new \GraphQL\Error\Error('Registration failed: '.$e->getMessage());
        }
    }
}
