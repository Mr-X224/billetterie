<?php

namespace App\Enum;

enum OrderChannel: string
{
    case WEB = 'web';
    case BOX_OFFICE = 'box_office';
}