<?php

namespace Fobia\Database\SphinxConnection;

/**
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'sphinx';
    }
}
