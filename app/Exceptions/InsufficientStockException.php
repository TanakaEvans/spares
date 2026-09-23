<?php

namespace App\Exceptions;

class InsufficientStockException extends DomainException
{
    public function __construct(
        private readonly int $partId,
        private readonly int $branchId,
        private readonly float $requested,
        private readonly float $available,
    ) {
        parent::__construct("Insufficient stock for part {$partId} at branch {$branchId}: requested {$requested}, available {$available}.");
    }

    public function userMessage(): string
    {
        return sprintf(
            'Not enough stock — %.2f requested but only %.2f available at this branch.',
            $this->requested,
            $this->available
        );
    }

    public function context(): array
    {
        return [
            'part_id' => $this->partId,
            'branch_id' => $this->branchId,
            'requested' => $this->requested,
            'available' => $this->available,
        ];
    }
}
