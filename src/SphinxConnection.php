<?php
/**
 * SphinxConnection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Database\MySqlConnection;

/**
 * Class SphinxConnection
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class SphinxConnection extends MySqlConnection
{
    /**
     * @var \Fobia\Database\SphinxConnection\SphinxQLDriverConnection
     */
    protected $sphinxQLConnection;

    /**
     * @return \Fobia\Database\SphinxConnection\SphinxQLDriverConnection
     */
    public function getSphinxQLDriversConnection()
    {
        if (null === $this->sphinxQLConnection) {
            $this->sphinxQLConnection = new SphinxQLDriverConnection($this->getPdo());
        }
        return $this->sphinxQLConnection;
    }

    /**
     * @return \Foolz\SphinxQL\Helper
     */
    public function getSphinxQLHelper()
    {
        return new Helper($this->getSphinxQLDriversConnection());
    }

    /**
     * @return \Foolz\SphinxQL\SphinxQL
     */
    public function createSphinxQL()
    {
        return new SphinxQL($this->getSphinxQLDriversConnection());
    }

    /**
     * @return \Foolz\SphinxQL\Facet
     */
    public function createFacet()
    {
        return new Facet($this->getSphinxQLDriversConnection());
    }

    /**
     * Run an insert or replace statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function replace($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new \Fobia\Database\SphinxConnection\Eloquent\Query\Grammar();
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Fobia\Database\SphinxConnection\Eloquent\Query\Builder
     */
    public function query()
    {
        return new \Fobia\Database\SphinxConnection\Eloquent\Query\Builder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }
}
