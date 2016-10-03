<?php
/**
 * Model.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent;

use App\Lib\Database\Sphinx\Eloquent\Query\Builder as QueryBuilder;
use App\Lib\Database\Sphinx\Eloquent\Query\Grammar as QueryGrammar;
use App\Lib\Database\Sphinx\Sphinx;
use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Match;
use Illuminate\Database\Query\Expression;

/**
 * App\Lib\Database\Eloquent\Model
 *
 * @method static \App\Lib\Database\Sphinx\Eloquent\Query\Builder whereMatch($match)   Созвучный поиск.
 * @method static \App\Lib\Database\Sphinx\Eloquent\Query\Builder match($column, $value = null, $half = false)   Созвучный поиск.
 * @method static \App\Lib\Database\Sphinx\Eloquent\Query\Builder withinGroupOrderBy($name, $asc = 'ASC')  Конструкция [WITHIN GROUP ORDER BY].
 * @method static \App\Lib\Database\Sphinx\Eloquent\Query\Builder whereMulti($name, $operator, $values) равенство в список.
 * @method static \App\Lib\Database\Sphinx\Eloquent\Query\Builder options($name, $value) Опции запроса [OPTION].
 * @method static \App\Lib\Database\Sphinx\Eloquent\Query\Builder addFacat($column, $orderBy) Конструкция запроса [FACAT].
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    protected static $tableFields;

    /**
     * База данных
     *
     * @var string
     */
    protected $connection = 'sphinx';

    /**
     * @var \Illuminate\Database\Connection
     */
    protected static $instance_connection;
    protected $perPage = 15;
    public $timestamps = false;
    public $incrementing = false;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * @inheritDoc
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /*
     * ===================
     * Main scopes
     * ===================
     */

    /**
     * Созвучный поиск
     *
     * @param \App\Lib\Sharding\Eloquent\Query\Builder $query
     * @param string $match
     * @return mixed
     */
    public function scopeWhereMatch($query, $match)
    {
        $db = $this->getConnection();
        if (!$match instanceof Expression) {
            $match = $db->getPdo()->quote($match);
        }

        if ($match instanceof \Closure) {
            $matchSphinxQL = Match::create(Sphinx::createSphinxQL());
            call_user_func_array($match, [$matchSphinxQL, $query]);
            $match = $matchSphinxQL->compile()->getCompiled();
        }

        $query->whereRaw($db->raw("MATCH({$match})"));

        return $query;
    }

    /**
     * Созвучный поиск через библиотеку SphinxQL
     *
     * @param $query
     * @param $column
     * @param null $value
     * @param bool $half
     * @return mixed
     */
    public function scopeMatch($query, $column, $value = null, $half = false)
    {
        $db = $this->getConnection();
        // SphinxQL generator
        $builder = $query->getQuery();
        if (empty($builder->whereMatch)) {
            $builder->whereMatch = Sphinx::createSphinxQL();
        }
        $query->getQuery()->whereMatch->match($column, $value, $half);

        return $query;
    }

    /**
     * Конструкция [WITHIN GROUP ORDER BY {col_name | expr_alias} {ASC | DESC}]
     *
     * @param \App\Lib\Database\Sphinx\Eloquent\Builder $query
     * @param string $name col_name
     * @param $asc
     * @return mixed
     */
    public function scopeWithinGroupOrderBy($query, $name, $asc = 'ASC')
    {
        $db = $this->getConnection(); // Поче без этого ФАТАЛ ? Может из за невнимательности переключеня веток что-то слетало
        $query->getQuery()->grouporders[$name] = $asc;
        return $query;
    }

    /**
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
     * @param \App\Lib\Database\Sphinx\Eloquent\Builder $query
     * @param $name     opt_name
     * @param $value    opt_value
     * @return mixed
     */
    public function scopeOptions($query, $name, $value)
    {
        $db = $this->getConnection();
        $query->getQuery()->options[] = [$name, $value];

        // если передать $model->options(null, null), произойдет чистка
        if ($name === null && $value === null) {
            $query->getQuery()->options = [];
        }

        return $query;
    }

    /**
     * Проверка списков MVA либо множественая проверка всех перечисленых значений
     *
     * Example:
     *     $model->whereMulti('tags', 1, 2, 3, '', [5, 6, 7], null)
     *     // .. WHERE tags = 1 AND tags = 2 tags = 3 tags = 5 tags = 6 tags = 7
     *
     * @param \App\Lib\Database\Sphinx\Eloquent\Builder $query
     * @param string $column
     * @param string $operator
     * @param mixed|array  $value
     * @return mixed
     */
    public function scopeWhereMulti($query, $column, $operator = null, $value = null)
    {
        if (is_string($operator) && in_array(strtolower($operator), ['in', 'not in', '=', '<', '>', '<=', '>=', '<>', '!='])) {
            $values = array_slice(func_get_args(), 3);
        } else {
            $values = array_slice(func_get_args(), 2);
            $operator = '=';
        }
        $operator = strtolower($operator);
        if ($ids = $this->filterParamsUint($values)) {
            if ($operator == 'in') {
                $query->whereIn($column, $ids);
            } elseif($operator == 'not in') {
                $query->whereNotIn($column, $ids);
            } else {
                foreach ($ids as $id) {
                    $query->where($column, $operator, $id);
                }
            }
        }
        return $query;
    }

    /**
     * Проверка столбца и значение по умолчанию [ EXIST('type', 0) AS itype ]
     *
     * Example:
     *      $model->addSelectExist('column', 0)
     *      // SELECT *, EXIST('column', 0) AS icolumn
     *
     * @param \App\Lib\Database\Sphinx\Eloquent\Builder $query
     * @param string $column    name
     * @param string $as_column name
     * @param int $default
     * @return \App\Lib\Database\Sphinx\Eloquent\Builder
     */
    public function scopeAddSelectExist($query, $column, $as_column = null, $default = 0)
    {
        $db = $this->getConnection();
        if ($as_column === null) {
            $as_column = 'i' . $column;
        }
        $default = (int)$default;
        $query->addSelect($db->raw("EXIST('{$column}', {$default}) AS {$as_column}"));
        return $query;
    }

    /**
     *
     * FACET {expr_list} [BY {expr_list}] [ORDER BY {expr | FACET()} {ASC | DESC}] [LIMIT [offset,] count]
     *
     * FACET clause. This Sphinx specific extension enables faceted search with subtree optimization.
     * It is capable of returning multiple result sets with a single SQL statement, without the need for complicated multi-queries.
     * FACET clauses should be written at the very end of SELECT statements with spaces between them.
     *
     * @param \App\Lib\Database\Sphinx\Eloquent\Builder $query
     * @param string $column expr_list
     * @param mixed|array $orderBy
     * @return \App\Lib\Database\Sphinx\Eloquent\Builder
     */
    public function scopeAddFacet($query, $column, $orderBy)
    {
        $query->getQuery()->facets[] = [
            'facet' => $column,
            'order_by' => $orderBy,
            'limit' => null,
        ];
        return $query;
    }
    
    /**
     * @param Builder $query
     * @param callable $callable
     *
     * @return Builder
     */
    public function scopeFacet($query, $callable)
    {
        $facet = Facet::create($this->getConnection()->getSphinxQLDriversConnection());
        $callable($facet);
        
        $sql = $facet->compileFacet()->getFacet();
        
        return $query;
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
        
        $args = array_flatten((array) $args);
        $args = array_filter((array) $args, function ($v) {
            return (($v !== null) && ($v !== ''));
        });
        if (!count($args)) {
            return false;
        }
        $ids = array_map('intval', $args);
        return array_unique(array_values($ids));
    }

    /**
     * Конвертирует тип поля MVA в список
     *
     * @param string $name название поля
     * @return array
     */
    protected function getMvaAttribute($name)
    {
        if (isset($this->attributes[$name]) && $this->attributes[$name] != '') {
            return explode(',', $this->attributes[$name]);
        }
        return [];
    }

    /*
     * ===================
     * Main static methods
     * ===================
     */

    /**
     * Список полей таблици, по модели
     *
     * @return array
     */
    public static function getTableFields()
    {
        if (self::$tableFields === null) {
            self::$tableFields = [];
        }

        $class = get_called_class();
        if (!in_array($class, self::$tableFields)) {
            $table = (new $class())->getTable();
            $fields = [];
            $db = \DB::connection('sphinx');
            $rows = $db->select("DESCRIBE {$table}");
            foreach ($rows as $row) {
                $fields[$row->Field] = $row->Type;
            }
            self::$tableFields[$class] = $fields;
        }

        return self::$tableFields[$class];
    }

    
    /*
     * ===================
     * Override methods
     * ===================
     */

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes()
    {
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());
        return $builder->setModel($this)->with($this->with);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();
        $grammar = new QueryGrammar;

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }

    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }
}
