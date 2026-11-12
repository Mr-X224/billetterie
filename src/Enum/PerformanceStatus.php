<?php

namespace App\Enum;

enum PerformanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case CANCELLED = 'cancelled';
}