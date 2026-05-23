<?php

declare(strict_types=1);

namespace App\Enums;

enum LocationType: string
{
    case CITY = 'city';
    case DISTRICT = 'district';
    case AREA = 'area';
}
