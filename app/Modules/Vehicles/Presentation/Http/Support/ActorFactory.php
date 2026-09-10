<?php

namespace App\Modules\Vehicles\Presentation\Http\Support;

use App\Models\User;
use App\Modules\Vehicles\Domain\Model\Actor;

final class ActorFactory
{
    public function fromUser(User $user): Actor
    {
        return new Actor((int) $user->getKey(), $user->is_admin);
    }
}
