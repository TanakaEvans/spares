<?php

namespace App\Exceptions;

class OverReceiptException extends DomainException
{
    public function __construct(
        private readonly string $partNumber,
        private readonly float $ordered,
        private readonly float $attempted,
        private readonly float $tolerancePct,
    ) {
        parent::__construct("Over-receipt on {$partNumber}: ordered {$ordered}, attempted total {$attempted} (tolerance {$tolerancePct}%).");
    }

    public function userMessage(): string
    {
        return sprintf(
            '%s: receiving this would total %.2f against %.2f ordered — beyond the %.0f%% tolerance. A supervisor must approve the over-receipt.',
            $this->partNumber,
            $this->attempted,
            $this->ordered,
            $this->tolerancePct
        );
    }

    public function context(): array
    {
        return [
            'part' => $this->partNumber,
            'ordered' => $this->ordered,
            'attempted' => $this->attempted,
            'tolerance_pct' => $this->tolerancePct,
        ];
    }
}
