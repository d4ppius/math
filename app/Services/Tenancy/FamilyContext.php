<?php

namespace App\Services\Tenancy;

use App\Models\Family;

/**
 * Request-scoped holder for the currently authenticated family, resolved by
 * the ResolveFamilyContext middleware from whichever guard (parent or child)
 * is active. Bound as a singleton in the service container.
 */
class FamilyContext
{
    private ?Family $family = null;

    public function set(Family $family): void
    {
        $this->family = $family;
    }

    public function get(): ?Family
    {
        return $this->family;
    }

    public function id(): ?int
    {
        return $this->family?->id;
    }

    public function has(): bool
    {
        return $this->family !== null;
    }
}
