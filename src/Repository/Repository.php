<?php
/**
 * Repository.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Repository;

use Illuminate\Support\Facades\Config;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class Repository
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
abstract class Repository extends BaseRepository
{
    protected $perPage = 6;

    public function __construct(\Illuminate\Container\Container $app, \Illuminate\Support\Collection $collection)
    {
        parent::__construct($app, $collection);

        if (Config::has('paginator.default_count_per_page')) {
            $this->perPage = Config::get('paginator.default_count_per_page');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($perPage = null, $columns = ['*'])
    {
        if ($perPage === null) {
            $perPage = $this->perPage;
        }

        $this->applyCriteria();
        return $this->model->paginate($perPage, $columns);
    }

    public function returnPaginate($params)
    {
        $perPage = $this->perPage;
        $columns = ['*'];

        if (!empty($params['limit'])) {
            $perPage = (int) $params['limit'];
        }

        // Paginator property
        $pageName = 'page';
        $page = null;
        if (!empty($params['loadPage']) && !empty($params['page'])) {
            $perPage = $perPage * $params['page'];
            $page = 1;
        }
        if (!empty($params['offset'])) {
            $page = ceil($params['offset'] / $perPage) + 1;
        }

        // Так уж и быть, позволим внедрять внешнии билдеры запроса,
        // чтож мы так ограничели разработчиков то.
        $this->applyCriteria();
        return $this->model->paginate($perPage, $columns, $pageName, $page);
    }
}
