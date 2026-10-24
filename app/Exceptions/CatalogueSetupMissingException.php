<?php

namespace App\Exceptions;

class CatalogueSetupMissingException extends DomainException
{
    /** @param  array<int,string>  $missingCodes */
    public function __construct(private readonly array $missingCodes)
    {
        parent::__construct('Supplier catalogue import: categories missing from the database: '.implode(', ', $missingCodes));
    }

    public function userMessage(): string
    {
        return 'The category tree is missing '.count($this->missingCodes).' categories the catalogue maps to ('
            .implode(', ', array_slice($this->missingCodes, 0, 8)).'). Load the latest reference data first: php artisan db:seed --class=ReferenceDataSeeder';
    }

    public function context(): array
    {
        return ['missing_category_codes' => $this->missingCodes];
    }
}
