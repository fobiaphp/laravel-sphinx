<?php

namespace Fobia\Database\SphinxConnection;

/**
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'sphinx';
    }
}
