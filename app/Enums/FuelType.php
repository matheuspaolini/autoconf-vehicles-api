<?php

namespace App\Enums;

enum FuelType: string
{
    case Gasoline = 'gasolina';
    case Ethanol = 'alcool';
    case Flex = 'flex';
    case Diesel = 'diesel';
    case Hybrid = 'hibrido';
    case Electric = 'eletrico';
}
