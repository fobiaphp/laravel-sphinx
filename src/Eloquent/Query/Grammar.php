<?php
/**
 * Grammar.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent\Query;

use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Match;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

/**
 * Class Grammar
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class Grammar extends BaseGrammar
{
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'match',
        'wheres',
        'groups',
        'grouporders',
        'havings',
        'orders',
        'limit',
        'options',
        'facets',
    ];

    /**
     * {@inheritdoc}
     */
    public function compileSelect(BaseBuilder $query)
    {
        if (is_array($query->columns)) {
            $query->columns = array_unique($query->columns);
        }

        return parent::compileSelect($query);
    }

    protected function compileGrouporders(BaseBuilder $query, $groups)
    {
        $sql = [];
        foreach ($groups as $k => $v) {
            $sql[] = $k . ' ' . $v;
        }
        return 'WITHIN GROUP ORDER BY ' . implode(', ', $sql);
    }

    protected function compileOptions(BaseBuilder $query, $options = null)
    {
        $sql = [];
        if (is_array($options)) {
            foreach ($options as $row) {
                $opt = $row[0];
                $opt_val = $row[1];

                // weights - Для опций, задающихся масивом
                if ($opt == 'field_weights' || $opt == 'index_weights') {
                    $weights = [];
                    if (is_array($opt_val)) {
                        foreach ($opt_val as $k => $v) {
                            $weights[] = "{$k} = " . (int) $v;
                        }
                    } else {
                        $weights[] = preg_replace("/\(|\)/", '', $opt_val);
                    }
                    $opt_val = '(' . implode(', ', $weights) . ')';
                }

                if ($opt == 'comment') {
                    $opt_val = $this->wrapValue2($opt_val);
                }

                $sql[$opt] = $opt . ' = ' . $opt_val;
            }
        }

        return 'OPTION ' . implode(', ', $sql);
    }

    /**
     * FACET {expr_list} [BY {expr_list}] [ORDER BY {expr | FACET()} {ASC | DESC}] [LIMIT [offset,] count]
     *
     * @param BaseBuilder $query
     * @param $facets
     * @return \Illuminate\Database\Query\Builder|string
     * @throws \Exception
     */
    protected function compileFacets(BaseBuilder $query, $facets)
    {
        $sql = [];
        $query = '';
        if (!empty($facets)) {
            foreach ($facets as $facet) {
                if (!$facet instanceof Facet) {
                    throw new \Exception('Not Facet');
                }
                // dynamically set the own SphinxQL connection if the Facet doesn't own one
                //if ($facet->getConnection() === null) {
                //    $facet->setConnection($query->getConnection());
                //    $sql[] = $facet->getFacet();
                //    // go back to the status quo for reuse
                //    $facet->setConnection();
                //} else {
                $sql[] = $facet->getFacet();
                //}
                //$facet = "FACET " .
            }

            $query .= "\n" . implode("\n", $sql);
        }

        return $query;
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $limit
     * @return string
     */
    protected function compileLimit(BaseBuilder $query, $limit)
    {
        $limit = 1000;
        $offset = 0;
        if (!is_null($query->limit)) {
            $limit = $query->limit;
        }
        if (!is_null($query->offset)) {
            $offset = $query->offset;
        }

        return 'LIMIT ' . ((int) $offset) . ', ' . ((int) $limit);
    }

    /**
     * @internal
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $offset
     * @return string
     */
    protected function compileOffset(BaseBuilder $query, $offset)
    {
        return '';
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string $table
     * @return string
     */
    protected function compileFrom(BaseBuilder $query, $table)
    {
        if (is_array($table)) {
            return 'FROM ' . implode(', ', $table);
        }
        return 'FROM ' . $this->wrapTable($table);
    }

    /**
     * Compiles the MATCH part of the queries
     * Used by: SELECT, DELETE, UPDATE
     *
     * @param Builder $queryBuilder
     * @param array $matchs
     * @return string The compiled MATCH
     */
    public function compileMatch(Builder $queryBuilder, $matchs)
    {
        $sphinxQL = $queryBuilder->getConnection()->createSphinxQL();
        $query = '';

        if (!empty($matchs)) {
            $matched = [];

            foreach ($matchs as $match) {
                $pre = '';
                if ($match['column'] instanceof \Closure) {
                    $sub = new Match($sphinxQL);
                    call_user_func($match['column'], $sub);
                    $pre .= $sub->compile()->getCompiled();
                } elseif ($match['column'] instanceof Match) {
                    $pre .= $match['column']->compile()->getCompiled();
                } elseif (empty($match['column'])) {
                    $pre .= '';
                } elseif (is_array($match['column'])) {
                    $pre .= '@(' . implode(',', $match['column']) . ') ';
                } else {
                    if (is_numeric($match['column'])) {
                        $match['column'] = '"' . $match['column'] . '"';
                    }
                    $pre .= '@' . $match['column'] . ' ';
                }

                if ($match['half']) {
                    $pre .= $sphinxQL->halfEscapeMatch($match['value']);
                } else {
                    $pre .= $sphinxQL->escapeMatch($match['value']);
                }
                if ($pre) {
                    $matched[] = '(' . $pre . ')';
                }
            }

            $matched = implode(' ', $matched);
            $query = 'WHERE MATCH(' . $sphinxQL->getConnection()->escape(trim($matched)) . ') ';
        }

        return $query;
    }

    public function compileWheres(BaseBuilder $query)
    {
        $where = parent::compileWheres($query);
        // If SphinxQL generator
        if (!empty($query->match)) {
            $where = (empty($where) ? '' : ' AND ') . substr($where, 5);
        }
        return $where;
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        return array_filter($bindings, 'is_string');
    }

    /**
     * {@inheritdoc}
     */
    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on it
        // own, and then joins them both back together with the "as" connector.
        if (strpos(strtolower($value), ' as ') !== false) {
            $segments = explode(' ', $value);

            if ($prefixAlias) {
                $segments[2] = $this->tablePrefix . $segments[2];
            }

            return $this->wrap($segments[0]) . ' as ' . $this->wrapValue($segments[2]);
        }

        $wrapped = [];

        $segments = explode('.', $value);

        // If the value is not an aliased table expression, we'll just wrap it like
        // normal, so if there is more than one segment, we will wrap the first
        // segments as if it was a table and the rest as just regular values.
        foreach ($segments as $key => $segment) {
            //if ($key == 0 && count($segments) == 2) {
            if ($key == 0 && count($segments) >= 2) {
                // $wrapped[] = '`'.$segments[1].'`';
            } else {
                $wrapped[] = $this->wrapValue($segment);
            }
        }

        return implode('.', $wrapped);
    }

    protected function wrapValue($value)
    {
        return $value;
    }

    /**
     * Форматирует в синтаксис sphinx либо null
     *
     * @param mixed $value
     * @return int|string|null
     */
    public function quoteBinding($value)
    {
        if ($value === null) {
            $value = 'null';
        } elseif ($value === true) {
            $value = 1;
        } elseif ($value === false) {
            $value = 0;
        } elseif (is_int($value)) {
            $value = (int) $value;
        } elseif (is_float($value)) {
            // Convert to non-locale aware float to prevent possible commas
            $value = sprintf('%F', $value);
        } elseif (is_array($value)) {
            // Supports MVA attributes
            $value = '(' . implode(', ', array_map('intval', $value)) . ')';
        } else {
            $value = null;
        }

        return $value;
    }

    protected function wrapValue2($value)
    {
        if ($value === '*') {
            return $value;
        }

        try {
            return \DB::connection('sphinx')->getPdo()->quote($value);
        } catch (\Exception $e) {
            $value = str_replace('\\', '\\\\', $value);
            return '\'' . str_replace('\'', '\\\'', $value) . '\'';
        }

        //return parent::wrapValue($value);
        //return Sphinx::getConnection()->getPdo()->quote($value);
    }

    public function parameter($value)
    {
        return $this->isExpression($value)
            ? $this->getValue($value)
            : (
                (($v = $this->quoteBinding($value)) !== null)
                    ? $v
                    : $this->wrapValue2($value)
            );
    }
}
