<?php
/**
 * Builder.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent\Query;

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Class Builder
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class Builder extends QueryBuilder
{
    /**
     * The current query value bindings.
     *
     * @var array
     */
    protected $bindings = [
        'select' => [],
        'join'   => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
        'union'  => [],
    ];

    public $grouporders;
    public $options;
    public $facets;
    public $whereMatch;

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * @inheritdoc
     */
    protected function runSelect()
    {
        $bindings = $this->getBindings();
        $sql = $this->toSql();
        foreach ($bindings as $k => $v) {
            if (is_integer($v) || is_float($v)) {
                $sql = preg_replace('/ \?/', ' ' . $v, $sql, 1);
                unset($bindings[$k]);
            } else {
                $sql = preg_replace('/ \?/', ' ?: ', $sql, 1);
            }
        }
        $sql = preg_replace('/ \?:/', ' ?', $sql);
        return $this->connection->select($sql, $bindings, !$this->useWritePdo);
    }


    /**
     * Replace a new record into the database.
     *
     * @param  array $values
     * @return bool
     *
     * @see \Illuminate\Database\Query\Grammars\Grammar::compileInsert
     */
    public function replace(array $values)
    {
        if (empty($values)) {
            return true;
        }
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient for building these
        // inserts statements by verifying the elements are actually an array.
        if (!is_array(reset($values))) {
            $values = [$values];
        }

        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient for building these
        // inserts statements by verifying the elements are actually an array.
        else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }
        // We'll treat every insert like a batch insert so we can easily insert each
        // of the records into the database consistently. This will make it much
        // easier on the grammars to just handle one type of record insertion.
        $bindings = [];

        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }

        $sql = $this->grammar->compileInsert($this, $values);
        $sql = str_replace('insert into ', 'replace into ', $sql);

        // Once we have compiled the insert statement's SQL we can execute it on the
        // connection and return a result as a boolean success indicator as that
        // is the same type of result returned by the raw connection instance.
        $bindings = $this->cleanBindings($bindings);
        //dd($sql);
        return $this->connection->affectingStatement($sql, $bindings); //  insert($sql, $bindings);
    }


    /**
     * @inheritdoc
     */
    public function update(array $values)
    {
        $bindings = array_values(array_merge($values, $this->getBindings()));

        $sql = $this->grammar->compileUpdate($this, $values);
        return $this->connection->update($sql, $this->cleanBindings($bindings));
    }

}
