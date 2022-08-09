<?php

namespace Takaden\Enums;

use App\Models\Package;
use App\Models\Series;
use App\Models\Video;

enum Purchasable: string
{
    case Video = Video::class;
    case Series = Series::class;
    case Package = Package::class;

    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }
}
