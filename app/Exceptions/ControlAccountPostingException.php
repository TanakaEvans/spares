<?php

namespace App\Exceptions;

class ControlAccountPostingException extends DomainException
{
    public function __construct(private readonly string $accountCode)
    {
        parent::__construct("Direct posting to control account {$accountCode} is not allowed.");
    }

    public function userMessage(): string
    {
        return "Account {$this->accountCode} is a control account — it can only be posted through its sub-ledger, never directly.";
    }

    public function context(): array
    {
        return ['account_code' => $this->accountCode];
    }
}
