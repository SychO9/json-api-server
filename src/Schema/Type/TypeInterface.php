<?php

namespace Tobyz\JsonApiServer\Schema\Type;

interface TypeInterface
{
    public function serialize(mixed $value): mixed;

    public function deserialize(mixed $value): mixed;

    public function validate(mixed $value, callable $fail): void;

    public function schema(): array;
}
