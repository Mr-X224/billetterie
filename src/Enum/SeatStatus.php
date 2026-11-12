<?php

namespace App\Enum;

enum SeatStatus: string
{
    case AVAILABLE = 'available';
    case HELD = 'held';
    case SOLD = 'sold';
}