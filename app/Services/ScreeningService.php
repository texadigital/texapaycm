<?php

namespace App\Services;

use App\Models\ScreeningCheck;
use App\Models\ScreeningResult;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ScreeningService
{
    /**
     * Run sanctions/PEP/adverse media screening for a user.
     * This is a provider-agnostic facade; integrate vendor(s) internally.
     */
    public function runUserScreening(User $user, string $reason = 'kyc_update'): array
    {
        // Persist a ScreeningCheck row
        $check = ScreeningCheck::create([
            'user_id' => $user->id,
            'type' => $reason,
            'provider' => config('services.screening.provider', 'internal'),
            'status' => 'running',
        ]);

        try {
            // Choose provider based on config, default internal stub
            $driver = config('services.screening.driver', 'internal');
            $results = null;
            if ($driver === 'smileid') {
                /** @var \App\Services\Screening\Provider $provider */
                $provider = app(\App\Services\Screening\SmileIdProvider::class);
                $results = $provider->screen($user);
            }
            if ($results === null) {
                // Internal default stub
                $results = [
                    'sanctions_hit' => false,
                    'pep_match' => false,
                    'adverse_media' => false,
                    'risk_score' => 10,
                    'decision' => 'pass', // pass|review|fail
                    'matches' => [],
                ];
            }

            // Admin test toggles override results
            $forceReview = (bool) \App\Models\AdminSetting::getValue('aml.screening.force_review', false);
            $forceSanctions = (bool) \App\Models\AdminSetting::getValue('aml.screening.force_sanctions', false);
            $forcePep = (bool) \App\Models\AdminSetting::getValue('aml.screening.force_pep', false);

            if ($forceSanctions || $forcePep || $forceReview) {
                $results = [
                    'sanctions_hit' => $forceSanctions,
                    'pep_match' => $forcePep,
                    'adverse_media' => $forceReview && !($forceSanctions || $forcePep),
                    'risk_score' => 80,
                    'decision' => 'review',
                    'matches' => [
                        'note' => 'Forced by AdminSetting for testing',
                    ],
                ];
            }

            // Save a summary ScreeningResult
            ScreeningResult::create([
                'screening_check_id' => $check->id,
                'match_type' => 'summary',
                'name' => $user->full_name ?? $user->name ?? ($user->email ?? (string) $user->id),
                'list_source' => 'aggregate',
                'score' => $results['risk_score'],
                'decision' => $results['decision'],
                'raw' => $results,
            ]);

            $check->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $results;
        } catch (\Throwable $e) {
            Log::error('Screening failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $check->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            return [
                'sanctions_hit' => false,
                'pep_match' => false,
                'adverse_media' => false,
                'risk_score' => 0,
                'decision' => 'error',
                'matches' => [],
            ];
        }
    }
}
