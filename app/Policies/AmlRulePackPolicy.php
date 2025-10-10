<?php

namespace App\Policies;

use App\Models\AmlRulePack;
use App\Models\User;

class AmlRulePackPolicy
{
    public function viewAny(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function view(User $user, AmlRulePack $model): bool { return (bool) ($user->is_admin ?? false); }
    public function create(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function update(User $user, AmlRulePack $model): bool { return (bool) ($user->is_admin ?? false); }
    public function delete(User $user, AmlRulePack $model): bool { return (bool) ($user->is_admin ?? false); }
}
