<?php

namespace App\Policies;

use App\Models\Foundation\RecoveryRequest;
use App\Models\Foundation\User;
use App\Services\RecoveryService;

class RecoveryRequestPolicy
{
    public function __construct(private RecoveryService $recovery) {}

    public function review(User $reviewer, RecoveryRequest $request): bool
    {
        return $this->recovery->canReview($reviewer, $request->user);
    }
}
