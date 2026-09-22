<?php

namespace App\Exceptions;

class MissingExchangeRateException extends DomainException
{
    public function __construct(
        private readonly string $currencyCode,
        private readonly string $date,
    ) {
        parent::__construct("No exchange rate for {$currencyCode} on or before {$date}.");
    }

    public function userMessage(): string
    {
        return "No exchange rate has been captured for {$this->currencyCode}. "
            .'Capture today\'s rate under System Administration › Currencies before continuing.';
    }

    public function context(): array
    {
        return ['currency' => $this->currencyCode, 'date' => $this->date];
    }
}
