<?php

namespace App\Exceptions;

class ClosedPeriodException extends DomainException
{
    public function __construct(
        private readonly string $periodName,
        private readonly string $attemptedDate,
    ) {
        parent::__construct("Period {$periodName} is closed; cannot post on {$attemptedDate}.");
    }

    public function userMessage(): string
    {
        return "Period {$this->periodName} is closed — use the current period date or contact your administrator.";
    }

    public function context(): array
    {
        return ['period' => $this->periodName, 'attempted_date' => $this->attemptedDate];
    }
}
