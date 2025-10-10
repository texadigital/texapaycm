<?php

namespace App\Policies;

use App\Models\AmlStr;
use App\Models\User;

class AmlStrPolicy
{
    public function viewAny(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function view(User $user, AmlStr $model): bool { return (bool) ($user->is_admin ?? false); }
    public function create(User $user): bool { return (bool) ($user->is_admin ?? false); }
    public function update(User $user, AmlStr $model): bool { return (bool) ($user->is_admin ?? false); }
    public function delete(User $user, AmlStr $model): bool { return (bool) ($user->is_admin ?? false); }
}
