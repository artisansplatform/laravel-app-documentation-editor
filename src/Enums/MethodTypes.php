<?php

namespace Misusonu18\DocumentEditor\Enums;

enum MethodTypes
{
    case PARAMS;
    case CALLBACK;

    public static function values(): array
    {
        return collect(self::cases())
            ->map(fn($case) => $case->name)
            ->toArray();
    }
}
