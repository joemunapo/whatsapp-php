<?php

namespace Joemunapo\Whatsapp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Joemunapo\Whatsapp\Whatsapp
 */
class Whatsapp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Joemunapo\Whatsapp\Whatsapp::class;
    }
}
