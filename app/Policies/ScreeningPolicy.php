<?php

namespace App\Policies;

use App\Models\ScreeningCheck;
use App\Models\User;

class ScreeningPolicy
{
    public function viewAny(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function view(User $user, ScreeningCheck $model): bool { return (bool) ($user->is_admin ?? false); }
    public function create(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function update(User $user, ScreeningCheck $model): bool { return (bool) ($user->is_admin ?? false); }
    public function delete(User $user, ScreeningCheck $model): bool { return (bool) ($user->is_admin ?? false); }
}
