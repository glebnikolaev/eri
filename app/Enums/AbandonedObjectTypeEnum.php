<?php

namespace App\Enums;

enum AbandonedObjectTypeEnum: int
{
    case HOUSE = 1;
    case LAND_PLOT = 2;

    public function label(): string
    {
        return match ($this) {
            self::HOUSE => 'Дом',
            self::LAND_PLOT => 'Участок',
        };
    }
}
