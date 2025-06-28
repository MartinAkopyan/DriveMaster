<?php

namespace App\GraphQL\Error;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Error\Debug;
use Rebing\GraphQL\Error\ValidationError;

class GraphQLErrorHandler
{
    public static function format(Error $error)
    {
        $formatted = FormattedError::createFromException($error);

        $previous = $error->getPrevious();

        if ($previous instanceof ValidationError) {
            $formatted['validation'] = $previous->getValidatorMessages();
        }

        return $formatted;
    }

    public static function handle(array $errors, callable $formatter): array
    {
        return array_map($formatter, $errors);
    }
}

