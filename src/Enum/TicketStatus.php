<?php

namespace App\Enum;

enum TicketStatus: string
{
    case PENDING = 'pending';
    case VALID = 'valid';
    case USED = 'used';
    case CANCELLED = 'cancelled';
}