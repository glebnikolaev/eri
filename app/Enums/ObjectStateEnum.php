<?php

namespace App\Enums;

enum ObjectStateEnum: int
{
    case AUCTION = 15;
    case FIX_PRICE = 16;

    public function label(): string
    {
        return match ($this) {
            self::AUCTION => 'Аукциион',
            self::FIX_PRICE => 'Без аукциона',
        };
    }
}
