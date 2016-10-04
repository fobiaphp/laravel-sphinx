<?php
/**
 * Model.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent;

use Fobia\Database\SphinxConnection\Eloquent\Query\Builder as QueryBuilder;
use Fobia\Database\SphinxConnection\Eloquent\Query\Grammar as QueryGrammar;


/**
 * App\Lib\Database\Eloquent\Model
 *
 * @method static \Fobia\Database\SphinxConnection\Eloquent\Query\Builder match($column, $value = null, $half = false)   Созвучный поиск.
 * @method static \Fobia\Database\SphinxConnection\Eloquent\Query\Builder withinGroupOrderBy($column, $asc = 'ASC')  Конструкция [WITHIN GROUP ORDER BY].
 * @method static \Fobia\Database\SphinxConnection\Eloquent\Query\Builder whereMulti($column, $operator, $values) равенство в список.
 * @method static \Fobia\Database\SphinxConnection\Eloquent\Query\Builder options($name, $value) Опции запроса [OPTION].
 * @method static \Fobia\Database\SphinxConnection\Eloquent\Query\Builder facat($callback) Конструкция запроса [FACAT].
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
    //public function newCollection(array $models = [])
    //{
    //    return new Collection($models);
    //}

    /*
     * ===================
     * Main scopes
     * ===================
     */


    /**
     * Проверка списков MVA либо множественая проверка всех перечисленых значений
     *
     * Example:
     *     $model->whereMulti('tags', 1, 2, 3, '', [5, 6, 7], null)
     *     // .. WHERE tags = 1 AND tags = 2 tags = 3 tags = 5 tags = 6 tags = 7
     *
     * @param \Fobia\Database\SphinxConnection\Eloquent\Builder $query
     * @param string $column
     * @param string $operator
     * @param mixed|array $value
     * @return mixed
    // */
    //public function scopeWhereMulti($query, $column, $operator = null, $value = null)
    //{
    //    if (is_string($operator) && in_array(strtolower($operator), ['in', 'not in', '=', '<', '>', '<=', '>=', '<>', '!='])) {
    //        $values = array_slice(func_get_args(), 3);
    //    } else {
    //        $values = array_slice(func_get_args(), 2);
    //        $operator = '=';
    //    }
    //    $operator = strtolower($operator);
    //    if ($ids = $this->filterParamsUint($values)) {
    //        if ($operator == 'in') {
    //            $query->whereIn($column, $ids);
    //        } elseif($operator == 'not in') {
    //            $query->whereNotIn($column, $ids);
    //        } else {
    //            foreach ($ids as $id) {
    //                $query->where($column, $operator, $id);
    //            }
    //        }
    //    }
    //    return $query;
    //}

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

    protected function asMva($value)
    {
        if (is_string($value)) {
            $value = preg_replace_sub('/[\(\)\s]/', $value);
            $value = explode(',', $value);
            $value = array_map('intval', $value);
        }
        return $value;
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

    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'mva':
                return $this->asMva($value);
            default:
                return parent::castAttribute($key, $value);
        }
    }


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
