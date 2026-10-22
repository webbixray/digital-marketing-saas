<?php

namespace App\Services\AI\Agent;

use App\Models\Agency;
use App\Models\User;

class AgentContext
{
    public function __construct(
        public readonly ?Agency $agency = null,
        public readonly ?User $user = null,
        public readonly int $agencyId = 0,
        public readonly int $userId = 0,
        public readonly array $config = [],
        public readonly string $tenantId = '',
        public readonly array $permissions = [],
    ) {}

    /**
     * Create context from a user object.
     */
    public static function fromUser(User $user): self
    {
        return new self(
            agency: $user->agency,
            user: $user,
            agencyId: $user->agency_id ?? 0,
            userId: $user->id,
            tenantId: (string) ($user->agency_id ?? ''),
        );
    }

    /**
     * Create context with just an agency ID.
     */
    public static function fromAgency(Agency $agency): self
    {
        return new self(
            agency: $agency,
            agencyId: $agency->id,
            tenantId: (string) $agency->id,
        );
    }
}
