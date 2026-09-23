<?php

namespace App\Exceptions;

class NoOpenPeriodException extends DomainException
{
    public function __construct(private readonly string $date)
    {
        parent::__construct("No financial period covers {$date}.");
    }

    public function userMessage(): string
    {
        return "No financial period exists for {$this->date}. Create the period under Finance › Periods before posting.";
    }

    public function context(): array
    {
        return ['date' => $this->date];
    }
}
