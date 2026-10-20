<?php

namespace App\Exceptions;

/**
 * A supervisor override was requested but the credentials offered do not
 * belong to a user with the required authority (or are simply wrong).
 */
class ApprovalDeniedException extends DomainException
{
    public function __construct(private readonly string $reason, private readonly string $permission)
    {
        parent::__construct("Approval denied: {$reason}");
    }

    public function userMessage(): string
    {
        return $this->reason;
    }

    public function context(): array
    {
        return ['permission' => $this->permission];
    }
}
