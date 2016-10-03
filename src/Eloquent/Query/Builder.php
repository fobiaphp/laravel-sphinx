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
    public $match;

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
    
    /**
     * OPTION clause (SphinxQL-specific)
     * Used by: SELECT
     *
     * @param string $name  Option name
     * @param string $value Option value
     *
     * @return SphinxQL
     */
    public function options($name, $value)
    {
        $this->options[] = [$name, $value];
        // если передать $model->options(null, null), произойдет чистка
        if ($name === null && $value === null) {
            $this->options = [];
        }
        return $this;
    }
    
    
    /**
     * WITHIN GROUP ORDER BY clause (SphinxQL-specific)
     * Adds to the previously added columns
     * Works just like a classic ORDER BY
     *
     * @param string $column    The column to group by
     * @param string $direction The group by direction (asc/desc)
     *
     * @return SphinxQL
     */
    public function withinGroupOrderBy($column, $direction = 'ASC')
    {
        $this->grouporders[$column] = $direction;
        return $this;
    }
    
    
    /**
     * MATCH clause (Sphinx-specific)
     *
     * @param mixed    $column The column name (can be array, string, Closure, or Match)
     * @param string   $value  The value
     * @param boolean  $half  Exclude ", |, - control characters from being escaped
     *
     * @return self
     */
    public function match($column, $value = null, $half = false)
    {
        if ($column === '*' || (is_array($column) && in_array('*', $column))) {
            $column = array();
        }
        
        $this->match[] = array('column' => $column, 'value' => $value, 'half' => $half);
        
        return $this;
    }
    
    public function matchQl(\Closure $callback)
    {
        $match = \Foolz\SphinxQL\Match::create($this->getConnection()->createSphinxQL());
        $callback($match);
        
        $this->match($match->compile()->getCompiled());
        
        return $this;
    }
    
    
    /**
     * Allows passing an array with the key as column and value as value
     * Used in: INSERT, REPLACE, UPDATE
     *
     * @param \Closure $facet
     * @return self
     */
    public function facet(\Closure $facet)
    {
        $this->facets[] = $facet;
        
        return $this;
    }
    
    
    /*
     * ===================
     * Override methods
     * ===================
     */
    
    /**
     * @return \Fobia\Database\SphinxConnection\SphinxConnection
     */
    public function getConnection()
    {
        return parent::getConnection();
    }
    
}
