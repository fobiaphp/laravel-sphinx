<?php
/**
 * Builder.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent\Query;

use Foolz\SphinxQL\Facet;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;

/**
 * Class Builder
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class Builder extends QueryBuilder
{
    /**
     * The current query value bindings.
     *
     * @var array
     */
    public $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
        'union' => [],
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
     * {@inheritdoc}
     */
    protected function runSelect()
    {
        $bindings = $this->getBindings();
        $sql = $this->toSql();
        foreach ($bindings as $k => $v) {
            $v = $this->grammar->quoteBinding($v);
            if ($v !== null) {
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
     * @param $value
     * @return mixed
     * @deprecated
     */
    protected function quoteBinding($value)
    {
        return $this->grammar->quoteBinding($value);
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
        } else {
            // Since every insert gets treated like a batch insert, we will make sure the
            // bindings are structured in a way that is convenient for building these
            // inserts statements by verifying the elements are actually an array.
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
                if (!is_array($value)) {
                    $bindings[] = $value;
                }
            }
        }

        $sql = $this->grammar->compileInsert($this, $values);
        $sql = preg_replace('/^insert into /iu', 'replace into ', $sql);

        // Once we have compiled the insert statement's SQL we can execute it on the
        // connection and return a result as a boolean success indicator as that
        // is the same type of result returned by the raw connection instance.
        $bindings = $this->cleanBindings($bindings);
        //dd($sql);
        return $this->connection->affectingStatement($sql, $bindings); //  insert($sql, $bindings);
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $values)
    {
        $bindings = array_values(array_merge($values, $this->getBindings()));
        $sql = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdate($bindings, $values)
        ));
    }

    /**
     * Проверка списков MVA либо множественая проверка всех перечисленых значений
     *
     * Example:
     *     $model->whereMulti('tags', 1, 2, 3, '', [5, 6, 7], null)
     *     // .. WHERE tags = 1 AND tags = 2 tags = 3 tags = 5 tags = 6 tags = 7
     *
     *     $model->whereMulti('tags', 'in', [1, 2, 3, [5, 6, 7]], [10, 11, 12])
     *     // .. WHERE tags in(1) AND tags in(2) AND tags in(3) AND tags in (5, 6, 7) AND tags in (10, 11, 12)
     *
     * @param string $column
     * @param string $operator
     * @param mixed|array $values
     * @return mixed
     */
    public function whereMulti($column, $operator = null, $values = null)
    {
        if (is_string($operator) && in_array(strtolower($operator), ['in', 'not in', '=', '<', '>', '<=', '>=', '<>', '!='])) {
            //$values = array_slice(func_get_args(), 3);
        } else {
            throw new \RuntimeException('Not defened operator');
        }

        $operator = strtolower($operator);
        if ($operator !== 'in' && $operator !== 'not in') {
            $ids = array_slice(func_get_args(), 2);
            $ids = $this->filterParamsUint($ids);
        } else {
            $ids = array_merge((array) $values, array_slice(func_get_args(), 3));
        }

        if ($ids) {
            foreach ($ids as $id) {
                if ($operator == 'in') {
                    $this->whereIn($column, (array) $id);
                } elseif ($operator == 'not in') {
                    $this->whereNotIn($column, (array) $id);
                } else {
                    $this->where($column, $operator, $id);
                }
            }
        }
        return $this;
    }

    /**
     * OPTION clause (SphinxQL-specific)
     * Used by: SELECT
     *
     * Опции запроса [OPTION opt_name = opt_value [, ...]]
     * Повторынй вызов добавит параметр.
     *
     * Example:
     *      $model->option('field_weights', 'title=10']); // options as string
     *      $model->option('field_weights', ['title' => 10, 'body' => 3]);  // options as array
     *      $model->option('index_weights', ['products_rt' => 10, 'body' => 1]);
     *      $model->option('ranker', 'bm25');
     *      $model->option('comment', 'my comment query');
     *
     *
     * OPTION:
     * 'agent_query_timeout', - integer (max time in milliseconds to wait for remote queries to complete, see agent_query_timeout under Index configuration options for details)
     * 'boolean_simplify' - 0 or 1, enables simplifying the query to speed it up
     * 'comment', - string, user comment that gets copied to a query log file
     * 'cutoff', - integer (max found matches threshold)
     * 'ranker' = bm25,
     * 'max_matches' = 3000,
     * 'agent_query_timeout' = 10000,
     * 'max_matches' = 1000,  - (default) - integer (per-query max matches value)
     * 'field_weights'= (title=10, body=3), -    a named integer list (per-field user weights for ranking)
     * 'index_weights' = (products_rt=10, body=3),    - a named integer list (per-index user weights for ranking)
     *
     * @param string $name  Option name
     * @param string $value Option value
     *
     * @return self
     */
    public function option($name, $value)
    {
        $this->options[] = [$name, $value];
        // если передать $model->option(null, null), произойдет чистка
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
     * @return self
     */
    public function withinGroupOrderBy($column, $direction = 'ASC')
    {
        $direction = mb_strtoupper($direction);
        if ($direction != 'ASC' && $direction != 'DESC') {
            throw new \RuntimeException('Undefined direction group (asc/desc) - ' . $direction);
        }
        $this->grouporders[$column] = $direction;
        return $this;
    }

    /**
     * MATCH clause (Sphinx-specific)
     *
     * @param mixed $column The column name (can be array, string, Closure, or Match)
     * @param string $value The value
     * @param bool $half Exclude ", |, - control characters from being escaped
     *
     * @return self
     */
    public function match($column, $value = null, $half = false)
    {
        if ($column === '*' || (is_array($column) && in_array('*', $column))) {
            $column = [];
        }

        $this->match[] = ['column' => $column, 'value' => $value, 'half' => $half];

        return $this;
    }

    /**
     * Allows passing an array with the key as column and value as value
     * Used in: INSERT, REPLACE, UPDATE
     *
     * @param \Closure|\Foolz\SphinxQL\Facet $callback
     * @return self
     * @throws \Exception
     */
    public function facet($callback)
    {
        if (!$callback instanceof Facet) {
            if (!$callback instanceof \Closure) {
                throw new \Exception('Not Facet');
            }
            $facet = new Facet($this->getConnection()->getSphinxQLDriversConnection());
            $callback($facet);
        } else {
            $facet = $callback;
        }

        $this->facets[] = $facet;

        return $this;
    }

    /**
     * Масив преобразуется в список целых числе, null и пустые строки игнорятся
     *
     * Example:
     *     filterParamsUint([1,2,null,4])  => [1,2,3]
     *     filterParamsUint([1,[2,[null,4]]])  => [1,2,3]
     *
     * @param $args
     * @return array|bool
     */
    protected function filterParamsUint($args)
    {
        $args = Arr::flatten((array) $args);
        $args = array_filter((array) $args, function ($v) {
            return ($v !== null) && ($v !== '');
        });
        if (!count($args)) {
            return false;
        }
        $ids = array_map('intval', $args);
        return array_unique(array_values($ids));
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
