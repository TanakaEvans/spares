<?php

namespace App\Exceptions;

class UnbalancedJournalException extends DomainException
{
    public function __construct(
        private readonly float $debits,
        private readonly float $credits,
    ) {
        parent::__construct("Journal does not balance: debits {$debits} vs credits {$credits}.");
    }

    public function userMessage(): string
    {
        return sprintf(
            'The journal does not balance — debits total %.2f but credits total %.2f. Adjust the lines so both sides are equal.',
            $this->debits,
            $this->credits
        );
    }

    public function context(): array
    {
        return ['debits' => $this->debits, 'credits' => $this->credits];
    }
}
