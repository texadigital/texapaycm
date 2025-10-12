<?php

namespace App\Services\Screening;

use App\Models\User;

interface Provider
{
    /**
     * Perform sanctions/PEP/adverse media screening on a user.
     * Must return a normalized array with keys:
     * - sanctions_hit: bool
     * - pep_match: bool
     * - adverse_media: bool
     * - risk_score: int (0-100)
     * - decision: string one of pass|review|fail
     * - matches: array arbitrary details
     */
    public function screen(User $user): array;
}
