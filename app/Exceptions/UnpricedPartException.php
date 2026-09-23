<?php

namespace App\Exceptions;

class UnpricedPartException extends DomainException
{
    public function __construct(private readonly string $partNumber)
    {
        parent::__construct("Part {$partNumber} has no selling price.");
    }

    public function userMessage(): string
    {
        return "{$this->partNumber} has no selling price on any applicable price list — add it to the retail price list before selling.";
    }

    public function context(): array
    {
        return ['part' => $this->partNumber];
    }
}
