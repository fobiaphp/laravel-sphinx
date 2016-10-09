# Sphinx Query Builder for Laravel

[![Build Status](https://travis-ci.org/fobiaphp/laravel-sphinx.svg?branch=master)](https://travis-ci.org/fobiaphp/laravel-sphinx)
[![Latest Stable Version](https://poser.pugx.org/fobia/laravel-sphinx/v/stable)](https://packagist.org/packages/fobia/laravel-sphinx)

Laravel-Sphinx Database connector, providing an expressive query builder, Eloquent, ActiveRecord style ORM


- [Config](#config)
- [Usage](#usage)
- [Query Builder](#query-builder)
    - [MATCH](#match)
    - [WITHIN GROUP, ORDER, OPTION](#within-group-order-option)
- [Api SphinxConnection](#api-sphinxconnection)
- [Resources](#resources)


## Installation

laravel-sphinx can be installed with [Composer](http://getcomposer.org)
by adding it as a dependency to your project's composer.json file.

```json
{
    "require": {
        "fobia/laravel-sphinx": "*"
    }
}
```

Please refer to [Composer's documentation](https://github.com/composer/composer/blob/master/doc/00-intro.md#introduction)
for more detailed installation and usage instructions.

## Config 



Для подключения `Sphinx` к `App`, достаточно добавиить `SphinxServiceProvider` в список провайдеров.

В файле `database.php` секции  `connections`  нужно дописать

        'sphinx' => [
            'driver'   => 'sphinx',
            'host'     => env('SPHINX_HOST', env('DB_HOST','127.0.0.1')),
            'port' => 9306,
        ],



## Usage


Получаем `connection` и строим запросы

    $db = \DB::connection('sphinx');

**Using The Query Builder**

```PHP
$users = $db->table('rt')->where('votes', '>', 100)->get();
```

**Using The Eloquent ORM**

```PHP
class Product extends \Fobia\Database\SphinxConnection\Eloquent\Model {} 

$product = Product::find(1);

$products = Product::where('votes', '>', 1)->get();
$products = Product::match('name', 'match text')->get();
```

**Attribute Casting**
Для результатов столбцов `attr_multi` можно указать формат, который конвертируется в масив.

Значение `'(1, 2, 3)'` колонки `attr_multi` конвертируется в масив `[1, 2, 3]` 

```PHP
class Product extends \Fobia\Database\SphinxConnection\Eloquent\Model 
{
    protected $casts = [
        'tags' => 'mva',
    ];
}
//
```


### Query Builder

    $sq = $db->table('rt');

При построении запроса используется строгая типизация значений (как и в SphinxQl). 
Поэтому `id = 1` и `id = '1'` не одно и тоже

* __integer__ используется для типом integer `attr_uint`
 
* __float__ используется для типом float `attr_float`

* __bool__ (integer) используется для типом bool `attr_bool`, будут преобразованы в integer (0 либо 1)

* __array__ (MVA) используется для вставки типом MVA `attr_multi`

    ```php
    $sq->insert([
        'id' => 1,
        'tags' => [1, 2, 3]
    ]);
    // INSERT INTO rt (id, tags) VALUES(1, (1, 2, 3))
   ```

* __string__ - для строковых значений, экранируются при запросе
    ```php
    $sq->insert([
        'id' => 1,
        'name' => "name 'text'"
    ]);
    // INSERT INTO rt (id, name) VALUES(1, 'name \'text\'')
   ```


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
    В качестве функции поиска `match` используется библиотека [SphinxQL::match](https://github.com/FoolCode/SphinxQL-Query-Builder#match) 


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
    $sq->whereMulti('id', '=', [1, 2, 3, [4, 5]]);
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

#### Replace

* __$sq->replace($values)__

    ```php
    $sq->replace([
        'id' => 1,
        'name' => 'text name'
    ]);
    ```


### API SphinxConnection

* [_\Foolz\SphinxQL\Drivers\Pdo\Connection_](https://github.com/FoolCode/SphinxQL-Query-Builder#connection) __$db->getSphinxQLDriversConnection()__
* [_\Foolz\SphinxQL\Helper_](https://github.com/FoolCode/SphinxQL-Query-Builder#helper) __$db->getSphinxQLHelper()__
* [_\Foolz\SphinxQL\SphinxQL_](https://github.com/FoolCode/SphinxQL-Query-Builder#sphinxql)  __$db->createSphinxQL()__


Resources
---------

  * [SphinxSearch](http://sphinxsearch.com/docs/current.html)
  * [SphinxQL](https://github.com/FoolCode/SphinxQL-Query-Builder)
  * [Laravel Eloquent](https://laravel.com/docs/5.3/eloquent)
