<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

class AdminActivity
{
    public static function log(string $action, ?Model $subject = null, array $before = [], array $after = [], array $meta = []): void
    {
        try {
            AdminActivityLog::create([
                'admin_user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject->getTable() : null,
                'subject_id' => $subject?->getKey(),
                'changes_before' => $before ?: null,
                'changes_after' => $after ?: null,
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }
}
