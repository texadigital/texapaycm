<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEvent;

class WebhookEventPolicy
{
    // View permissions: allow admins to view; deny others by default
    public function viewAny(?User $user): bool
    {
        return (bool) ($user?->is_admin ?? false);
    }

    public function view(?User $user, WebhookEvent $event): bool
    {
        return (bool) ($user?->is_admin ?? false);
    }

    // Read-only: disallow writes even for admins unless later expanded
    public function create(User $user): bool { return false; }
    public function update(User $user, WebhookEvent $event): bool { return false; }
    public function delete(User $user, WebhookEvent $event): bool { return false; }
    public function restore(User $user, WebhookEvent $event): bool { return false; }
    public function forceDelete(User $user, WebhookEvent $event): bool { return false; }
}
