<?php

namespace App\Enum;

enum EventType: string
{
    case CONCERT = 'concert';
    case THEATRE = 'theatre';
    case CONFERENCE = 'conference';
}