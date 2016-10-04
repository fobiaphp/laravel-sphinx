# Sphinx

Поисковой движок `Sphinx` синтаксис запроса

- [Usage](#usage)
- [Model](#model)
- [Repository](#repository)
- [EXAMPLE](#example)


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

# Usage

Весь код вынесен отдельно от основного `App`. Основная задача `Sphinx` и данного пакета
исключительно созвучный поиск и поиск некоторым параметрам (раз уж он в оперативе). 
Взоимодействия основного `App` происходит через интерфейсы `Model` и `Repository`.



### Config 

Для подключения `Sphinx` к `App`, достаточно добавиить `SphinxServiceProvider` в список провайдеров.

В файле `database.php` секции  `connections`  нужно дописать

        'sphinx' => [
            'driver'   => 'sphinx',
            'host'     => env('SPHINX_HOST', env('DB_HOST','127.0.0.1')),
            'port' => 9306,
        ],



```php
class ProductModel extends \Fobia\Database\SphinxConnection\Eloquent
{
    protected $table = 'products';
}
```
