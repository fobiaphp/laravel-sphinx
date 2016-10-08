<?php
/**
 * SphinxConnection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Closure;
use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Database\MySqlConnection;


/**
 * Class SphinxConnection
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class SphinxConnection extends MySqlConnection
{
    /**
     * @var \Fobia\Database\SphinxConnection\SphinxQLDriversConnection
     */
    protected $sphinxQLConnection;

    /**
     * @inheritDoc
     */
    public function __construct($pdo, $database, $tablePrefix, array $config)
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
    }

    /**
     * @return \Fobia\Database\SphinxConnection\SphinxQLDriversConnection
     */
    public function getSphinxQLDriversConnection()
    {
        if (null === $this->sphinxQLConnection) {
            $this->sphinxQLConnection = new SphinxQLDriversConnection($this->getPdo());
        }
        return $this->sphinxQLConnection;
    }

    /**
     * @return \Foolz\SphinxQL\Helper
     */
    public function getSphinxQLHelper()
    {
        return Helper::create($this->getSphinxQLDriversConnection());
    }

    /**
     * @return \Foolz\SphinxQL\SphinxQL
     */
    public function createSphinxQL()
    {
        return SphinxQL::create($this->getSphinxQLDriversConnection());
    }

    /**
     * @return \Foolz\SphinxQL\Facet
     */
    public function createFacet()
    {
        return Facet::create($this->getSphinxQLDriversConnection());
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
     * Run a select statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) use ($useReadPdo) {
            if ($me->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);

            $statement->execute($me->prepareBindings($bindings));

            return $statement->fetchAll($me->getFetchMode());
        });
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
        return new \Fobia\Database\SphinxConnection\Eloquent\Query\Builder($this, $this->getQueryGrammar(),
            $this->getPostProcessor());
    }
}
