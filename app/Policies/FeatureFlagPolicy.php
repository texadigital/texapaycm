<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Settings\FeatureFlag;

class FeatureFlagPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) ($user->is_admin ?? false) || $this->isRole($user, ['Ops','Compliance','Finance','ReadOnly']);
    }

    public function view(User $user, FeatureFlag $flag): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function update(User $user, FeatureFlag $flag): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function delete(User $user, FeatureFlag $flag): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function restore(User $user, FeatureFlag $flag): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    public function forceDelete(User $user, FeatureFlag $flag): bool
    {
        return false;
    }

    protected function isRole(User $user, array $roles): bool
    {
        // Placeholder for future role system; today only is_admin is available
        return false;
    }
}
