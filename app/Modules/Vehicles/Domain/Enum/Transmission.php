<?php

namespace App\Modules\Vehicles\Domain\Enum;

enum Transmission: string
{
    case Manual = 'manual';
    case Automatic = 'automatico';
}
