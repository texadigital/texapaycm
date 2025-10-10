<?php

namespace App\Policies;

use App\Models\EddCase;
use App\Models\User;

class EddCasePolicy
{
    public function viewAny(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function view(User $user, EddCase $model): bool { return (bool) ($user->is_admin ?? false); }
    public function create(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function update(User $user, EddCase $model): bool { return (bool) ($user->is_admin ?? false); }
    public function delete(User $user, EddCase $model): bool { return (bool) ($user->is_admin ?? false); }
}
