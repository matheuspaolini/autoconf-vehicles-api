<?php

namespace App\Modules\Vehicles\Domain\Model;

final readonly class Actor
{
    public function __construct(
        public int $userId,
        public bool $isAdministrator,
    ) {}
}
