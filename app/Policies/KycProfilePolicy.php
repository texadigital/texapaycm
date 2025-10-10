<?php

namespace App\Policies;

use App\Models\KycProfile;
use App\Models\User;

class KycProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function view(User $user, KycProfile $profile): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function create(User $user): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function update(User $user, KycProfile $profile): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function delete(User $user, KycProfile $profile): bool
    {
        return (bool) ($user->is_admin ?? false);
    }
}
