<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TeamRole: string implements HasColor, HasLabel
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Member = 'member';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Manager => 'warning',
            self::Member => 'gray',
        };
    }
}
