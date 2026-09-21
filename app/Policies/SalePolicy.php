<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function view(User $user, Sale $sale): bool { return $user->isAdmin() || $sale->user_id === $user->id || $sale->completed_by === $user->id; }
    public function discard(User $user, Sale $sale): bool { return $user->isAdmin() || $sale->user_id === $user->id; }
}
