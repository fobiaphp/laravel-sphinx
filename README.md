# Sphinx

Laravel Sphinx Database connector for Eloquent


- [Usage](#usage)
- [Config](#config)
- [Resources](#resources)


# Usage

```sql
SELECT
    select_expr [, select_expr ...]
    FROM index [, index2 ...]
    [WHERE where_condition]
    [GROUP [N] BY {col_name | expr_alias} [, {col_name | expr_alias}]]
    [WITHIN GROUP ORDER BY {col_name | expr_alias} {ASC | DESC}]
    [HAVING having_condition]
    [ORDER BY {col_name | expr_alias} {ASC | DESC} [, ...]]
    [LIMIT [offset,] row_count]
    [OPTION opt_name = opt_value [, ...]]
    [FACET facet_options[ FACET facet_options][ ...]]
```

### Config 



Для подключения `Sphinx` к `App`, достаточно добавиить `SphinxServiceProvider` в список провайдеров.

В файле `database.php` секции  `connections`  нужно дописать

        'sphinx' => [
            'driver'   => 'sphinx',
            'host'     => env('SPHINX_HOST', env('DB_HOST','127.0.0.1')),
            'port' => 9306,
        ],


После можно описать модели и работать подобно Laravel Eloquent

```php
class Product extends \Fobia\Database\SphinxConnection\Eloquent
{
    protected $table = 'products';
}


Product::whereMulti('id', '=', [1, 2, 3, 4]);
Product::match($index);

```


### API

* _\Foolz\SphinxQL\Drivers\Pdo\Connection_ __SphinxConnection::getSphinxQLDriversConnection()__
* _\Foolz\SphinxQL\Helper_ __SphinxConnection::getSphinxQLHelper()__
* _\Foolz\SphinxQL\SphinxQL_ __SphinxConnection::createSphinxQL()__



#### MATCH

* __$sq->match($column, $value, $half = false)__

	Search in full-text fields. Can be used multiple times in the same query. Column can be an array. Value can be an Expression to bypass escaping (and use your own custom solution).

    ```php
    <?php
    $sq->match('title', 'Otoshimono')
        ->match('character', 'Nymph')
        ->match(array('hates', 'despises'), 'Oregano');
      
    $sq->match(function(\Foolz\SphinxQL\Match $m) {
          $m->not('text');
    });
    ```

	See [SphinxQL::match](https://github.com/FoolCode/SphinxQL-Query-Builder#match) 



#### WITHIN GROUP, ORDER, OPTION

* __$sq->withinGroupOrderBy($column, $direction = 'ASC')__

	`WITHIN GROUP ORDER BY $column [$direction]`

	Direction can be omitted with `null`, or be `ASC` or `DESC` case insensitive.

* __$sq->orderBy($column, $direction = null)__

	`ORDER BY $column [$direction]`

	Direction can be omitted with `null`, or be `ASC` or `DESC` case insensitive.

* __$sq->option($name, $value)__

	`OPTION $name = $value`

	Set a SphinxQL option such as `max_matches` or `reverse_scan` for the query.


#### whereMulti

* __$sq->whereMulti($column, $operator, $values)__

    Все параметры преобразуются в плоский масив
    
    ```php
    $sq->whereMulti('id', '=', [1, 2, 3 [4, 5]]);
    // WHERE id = 1 and id = 2 and id = 3 and id = 4 and id = 5
    ```
    
    Для `in` и `not in` выглядит иначе 
    ```php
    $sq->whereMulti('id', 'in', [1, 2, 3]);
    // WHERE id in (1) and id in (2) and id in (3) 
    ```
    
    ```php
    $sq->whereMulti('id', 'in', [1, [20, 21], [30, 31]]);
    // WHERE id in (1) and id in (20, 21) and id in (30, 31) 
  
    // Эквивалентно
    $sq->whereMulti('id', 'in', 1, [20, 21], [30, 31]);
    // WHERE id in (1) and id in (20, 21) and id in (30, 31) 
    ```



Resources
---------

  * [SphinxSearch](http://sphinxsearch.com/docs/current.html)
  * [SphinxQL](https://github.com/FoolCode/SphinxQL-Query-Builder)
  * [Laravel Eloquent](https://laravel.com/docs/5.3/eloquent)
