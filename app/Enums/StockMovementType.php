<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Initial = 'initial';
    case In = 'in';
    case Sale = 'sale';
    case VoidReturn = 'void_return';
    case Adjustment = 'adjustment';
}
