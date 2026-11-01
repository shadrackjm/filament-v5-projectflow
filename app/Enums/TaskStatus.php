<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasIcon, HasLabel
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::InReview => 'In review',
            self::Done => 'Done',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::InProgress => 'warning',
            self::InReview => 'info',
            self::Done => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Todo => 'heroicon-o-inbox',
            self::InProgress => 'heroicon-o-bolt',
            self::InReview => 'heroicon-o-eye',
            self::Done => 'heroicon-o-check',
        };
    }
}
