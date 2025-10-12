<?php

namespace App\Policies;

use App\Models\User;

class AdminOnlyPolicy
{
    public function before(User $user, string $ability)
    {
        // Single gate: only admins allowed
        return (bool) ($user->is_admin ?? false);
    }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, $model): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, $model): bool { return true; }
    public function delete(User $user, $model): bool { return true; }
}
