<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Status: int implements HasLabel, HasColor
{
    case FORPURCHASE = 1;
    case OWNED = 2;
    case READING = 3;
    case READ = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FORPURCHASE => 'For Purchase',
            self::OWNED => 'Owned',
            self::READING => 'Reading',
            self::READ => 'Read',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FORPURCHASE => 'danger',
            self::OWNED => 'info',
            self::READING => 'warning',
            self::READ => 'success',
        };
    }

    public static function getColors(): array
    {
        return [
            'For Purchase' => 'danger',
            'Owned' => 'info',
            'Reading' => 'warning',
            'Read' => 'success',
        ];
    }
}
