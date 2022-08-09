<?php

namespace Takaden\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Takaden\Takaden
 */
class Takaden extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Takaden\Takaden::class;
    }
}
