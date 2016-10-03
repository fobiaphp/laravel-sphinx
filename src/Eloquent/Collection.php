<?php
/**
 * Collection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;
use App\Helpers\Institution as HelperInstitution;
use App\Lib\Sharding\ShardChooser;

/**
 * Class Collection.
 *
 * Колекция объектов моделей сфинкса, полученыя через выборги.
 * Жадная загрузка сгруперует модели по их спотам, подготовит запросы, а после выполнит поочередно. В результате
 * модели отношений будут иницализированы  со своих родных спотов с минимальным количиством запросов.
 *
 * Example:
 *      // Получаем колекцию
 *      $result = SphinxInstitutionAddress::whereMatch('Пицца')->limit(20)->get();
 *      // Подгружаем картиноччки (жадно, отложеная загрузка)
 *      $result->load('image');
 *      // Огонь, давайте глянем че там
 *      $images = $resilt->pluck('image', 'id')->toArray();
 *      print_r($images);
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class Collection extends BaseCollection
{
    /**
     * @inheritDoc
     */
    public function load($relations)
    {
        $collection = $this;
        if (!count($this->items) > 0) {
            return $this;
        }

        /** @var ShardChooser $shardChooser */
        // Сохраним сылку на объект, чтоб не дергать его циклично через фасад \ShardChooser::getShardByEntity()
        $shardChooser = app('ShardChooser');
        // Свойство модели, по которому можно индетифицировать шард
        $shardKey = 'institution_id';

        $grouped = $collection->groupBy(function ($item, $key) use ($shardChooser, $shardKey) {
            /** @var \Illuminate\Database\Eloquent\Model $item */
            if (!empty($item->partner_id)) {
                $partnerId = $item->partner_id;
            } else {
                $_id = $item->$shardKey;
                $partnerId = HelperInstitution::getPartnerIdByInstitutionId($_id);
            }
            $spot = $shardChooser->getShardByEntity($item->getTable(), $partnerId)->spot_number;
            return (int)$spot;
        });

        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $relations = (array)$relations;
        foreach ($grouped as $group) {
            $query = $group->first()->newQuery()->with($relations);
            $query->items = $query->eagerLoadRelations($group->items);
        }

        //return parent::load($relations);
        return $collection;
    }

}
