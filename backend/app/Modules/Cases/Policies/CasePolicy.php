<?php

declare(strict_types=1);

namespace App\Modules\Cases\Policies;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** Dentists see only their own cases; anyone else's case is reported as not found (§9.4). */
final class CasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Dentist->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Dentist->value) && $user->isActive() && ! $user->needsProfile();
    }

    public function view(User $user, DentalCase $case): Response
    {
        return $case->dentist_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, DentalCase $case): Response
    {
        return $this->view($user, $case);
    }
}
