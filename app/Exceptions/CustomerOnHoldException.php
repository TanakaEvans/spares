<?php

namespace App\Exceptions;

class CustomerOnHoldException extends DomainException
{
    public function __construct(
        private readonly string $customerName,
        private readonly ?string $reason,
    ) {
        parent::__construct("Customer {$customerName} is on hold.");
    }

    public function userMessage(): string
    {
        return "{$this->customerName} is on credit hold".($this->reason ? " ({$this->reason})" : '').' — account sales are blocked. Cash or card only until the hold is lifted.';
    }

    public function context(): array
    {
        return ['customer' => $this->customerName, 'reason' => $this->reason];
    }
}
