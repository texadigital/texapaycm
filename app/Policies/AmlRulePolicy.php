<?php

namespace App\Policies;

use App\Models\AmlRule;
use App\Models\User;

class AmlRulePolicy
{
    public function viewAny(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function view(User $user, AmlRule $model): bool { return (bool) ($user->is_admin ?? false); }
    public function create(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function update(User $user, AmlRule $model): bool { return (bool) ($user->is_admin ?? false); }
    public function delete(User $user, AmlRule $model): bool { return (bool) ($user->is_admin ?? false); }
}
