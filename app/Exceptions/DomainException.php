<?php

namespace App\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    abstract public function userMessage(): string;

    /** @return array<string, mixed> */
    abstract public function context(): array;
}
