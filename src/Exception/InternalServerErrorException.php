<?php

namespace Tobyz\JsonApiServer\Exception;

use RuntimeException;
use Tobyz\JsonApiServer\Exception\Concerns\SingleError;

class InternalServerErrorException extends RuntimeException implements
    ErrorProviderInterface,
    Sourceable
{
    use SingleError;

    public function getJsonApiStatus(): string
    {
        return '500';
    }
}
