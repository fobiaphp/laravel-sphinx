<?php
/**
 * Grammar.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent\Query;

use App\Lib\Database\Sphinx\Sphinx;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;
use Illuminate\Database\Query\Builder as BaseBuilder;

/**
 * Class Grammar
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class Grammar extends BaseGrammar
{
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'whereMatch',
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
     * @inheritdoc
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
        return 'WITHIN GROUP ORDER BY ' . implode(", ", $sql);
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
                        foreach($opt_val as $k=>$v) {
                            $weights[] = "{$k} = " . (int)$v;
                        }
                    } else {
                        $weights[] = preg_replace("/\(|\)/", "", $opt_val);
                    }
                    $opt_val = "(" . implode(", ", $weights) . ")";
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
     * @param \Illuminate\Database\Query\Builder $query
     * @param $facets
     * @return string
     */
    protected function compileFacets(BaseBuilder $query, $facets)
    {
        $sql = [];
        if (is_array($facets)) {
            foreach ($facets as $row) {
                $facet = "FACET " . implode(", ", (array)$row['facet']);

                if (!empty($row['order_by'])) {
                    $facet .= " ORDER BY " .  implode(", ", (array)$row['order_by']);
                }
                $sql[] = $facet;
            }

        }

        if ($sql) {
            $sql = "\n" .  implode("\n", $sql);
        }
        return $sql;
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

        return 'limit ' . ((int)$offset) . ', ' . ((int)$limit);
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
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(BaseBuilder $query, $table)
    {
        if (is_array($table)) {
            return 'from ' . implode(", ", $table);
        }
        return 'from ' . $this->wrapTable($table);
    }

    public function compileWhereMatch(BaseBuilder $query, $sphinxQl)
    {
        $match = $sphinxQl->compileMatch();
        return $match;
    }

    public function compileWheres(BaseBuilder $query)
    {
        $where =  parent::compileWheres($query);
        // If SphinxQL generator
        if (!empty($query->whereMatch)) {
            $where = ' AND ' . substr($where, 5);
        }
        return $where;
    }

    /**
     * @inheritdoc
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
            if ($key == 0 && count($segments) == 2) {
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

    protected function wrapValue2($value)
    {
        if ($value === '*') {
            return $value;
        }

        if (preg_match('/^\[[\d, ]+\]$/', $value)) {
            return "(" . substr($value, 1, -1) . ")";
        }

        return Sphinx::getConnection()->getPdo()->quote($value);
    }

    public function parameter($value)
    {
        return $this->isExpression($value)
            ? $this->getValue($value)
            : ((is_numeric($value) || is_integer($value) || is_float($value)) ? $value : $this->wrapValue2($value));
    }
}
