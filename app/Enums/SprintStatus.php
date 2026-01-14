<?php

namespace App\Enums;

enum SprintStatus: string
{
    case Planned = 'PLANNED';
    case Active = 'ACTIVE';
    case Closed = 'CLOSED';
}

