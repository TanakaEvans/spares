<?php

namespace App\Services\Health;

/** One health-check outcome. status: ok | warn | fail. */
final class HealthResult
{
    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly string $label,
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $fix = null,   // fix route name, if a one-click remedy exists
        public readonly ?string $link = null,  // route name to the offending records
    ) {
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'group' => $this->group,
            'label' => $this->label,
            'status' => $this->status,
            'message' => $this->message,
            'fix' => $this->fix,
            'link' => $this->link,
        ];
    }
}
