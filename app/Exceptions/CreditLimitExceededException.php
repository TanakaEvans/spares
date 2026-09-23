<?php

namespace App\Exceptions;

class CreditLimitExceededException extends DomainException
{
    public function __construct(
        private readonly string $customerName,
        private readonly float $limit,
        private readonly float $balance,
        private readonly float $attempted,
    ) {
        parent::__construct("Credit limit exceeded for {$customerName}: limit {$limit}, balance {$balance}, attempted {$attempted}.");
    }

    public function userMessage(): string
    {
        return sprintf(
            '%s is over their credit limit — limit %.2f, current balance %.2f, this sale would add %.2f. Take payment now, or get a supervisor to review the limit.',
            $this->customerName, $this->limit, $this->balance, $this->attempted
        );
    }

    public function context(): array
    {
        return ['customer' => $this->customerName, 'limit' => $this->limit, 'balance' => $this->balance, 'attempted' => $this->attempted];
    }
}
