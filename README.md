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

After updating composer, add the ServiceProvider to the providers array in config/app.php

```php
Fobia\Database\SphinxConnection\SphinxServiceProvider::class,
```

Finally you can just add `Sphinx Connection` to the database array in config/database.php 

```php
    'sphinx' => [
        'driver'   => 'sphinx',
        'host'     => env('SPHINX_HOST', env('DB_HOST','127.0.0.1')),
        'port' => 9306,
        'database' => '',
    ],
```

## Usage

Get a connection and build queries

```php
    $db = \DB::connection('sphinx');
```

**Using The Query Builder**

```php
$users = $db->table('rt')->where('votes', '>', 100)->get();
```

**Using The Eloquent ORM**

```php
class Product extends \Fobia\Database\SphinxConnection\Eloquent\Model {} 

$product = Product::find(1);

$products = Product::where('votes', '>', 1)->get();
$products = Product::match('name', 'match text')->get();
```

**Attribute Casting**
For the results of the column `attr_multi` can choose the format, which is converted to an array.

The values of `'(1, 2, 3)'` for column type `attr_multi` converted to an array `[1, 2, 3]` 

```php
class Product extends \Fobia\Database\SphinxConnection\Eloquent\Model 
{
    protected $casts = [
        'tags' => 'mva',
    ];
}
```


### Query Builder

```php
    $sq = $db->table('rt');
```

For the build a query, using strong typing of values (how in SphinxQl). 
> Notice: __`id = 1`__ and __`id = '1'`__ not the same

* __integer__ It is used to type integer `attr_uint`
 
* __float__ It is used to type float `attr_float`

* __bool__ (integer) It is used to type bool `attr_bool`, will be converted to integer (0 or 1)

* __array__ (MVA) It is used to type MVA `attr_multi`

    ```php
    $sq->insert([
        'id' => 1,
        'tags' => [1, 2, 3]
    ]);
    // Output: INSERT INTO rt (id, tags) VALUES(1, (1, 2, 3))
   ```

* __string__ - string values, escaped when requested
    ```php
    $sq->insert([
        'id' => 1,
        'name' => "name 'text'"
    ]);
    // Output: INSERT INTO rt (id, name) VALUES(1, 'name \'text\'')
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

    For a function `match` used library [SphinxQL::match](https://github.com/FoolCode/SphinxQL-Query-Builder#match) 


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

    All parameters converted to a flat array
    ```php
    $sq->whereMulti('id', '=', [1, 2, 3, [4, 5]]);
    // Output: WHERE id = 1 and id = 2 and id = 3 and id = 4 and id = 5
    ```
    

    For the `in` and `not in` is different
    ```php
    $sq->whereMulti('id', 'in', [1, 2, 3]);
    // Output: WHERE id in (1) and id in (2) and id in (3) 
    ```
    
    ```php
    $sq->whereMulti('id', 'in', [1, [20, 21], [30, 31]]);
    // Output: WHERE id in (1) and id in (20, 21) and id in (30, 31) 
  
    // Equivalently
    $sq->whereMulti('id', 'in', 1, [20, 21], [30, 31]);
    // Output: WHERE id in (1) and id in (20, 21) and id in (30, 31) 
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
