<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Voided = 'voided';
    case Discarded = 'discarded';
}
