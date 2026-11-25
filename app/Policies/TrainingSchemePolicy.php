<?php

namespace App\Policies;

use App\Models\TrainingScheme;
use App\Models\User;

class TrainingSchemePolicy
{
    public function update(User $user, TrainingScheme $scheme): bool
    {
        return $user->id === $scheme->user_id;
    }

    public function delete(User $user, TrainingScheme $scheme): bool
    {
        return $user->id === $scheme->user_id;
    }
}
