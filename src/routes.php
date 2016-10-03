<?php
/**
 * routes.php file
 *
 * @todo       [debug] отладочный файл
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

use App\Facades\Dictionary;
use App\Lib\Database\Sphinx\DataAccess\InstitutionAddressDataAccess;
use App\Lib\Database\Sphinx\DataAccess\ProductsDataAccess;
use App\Lib\Database\Sphinx\DataAccess\SphinxDataAccess;
use App\Lib\Database\Sphinx\Models\SphinxInstitutionAddress;
use App\Lib\Database\Sphinx\Models\SphinxProducts;
use App\Lib\Database\Sphinx\Repository\SphinxInstitutionAddressRepository;
use App\Lib\Database\Sphinx\Repository\SphinxProductsRepository;
use App\Lib\Database\Sphinx\Sphinx as DBSphinx;
use App\Models\Institution;
use App\Models\Products;
use App\Models\Properties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

// TODO: [debug] эксперементальные вычисления доступные по URL /tests-sphinx/

// Обновить запись Institution по ID
Route::get('/update/{id?}', function ($id = 1) {
    dump('Обновить запись Institution по ID: ' . $id);

    $dataAccessor = \App::make(SphinxDataAccess::class);
    /** @var SphinxDataAccess $dataAccessor */
    //$result = $dataAccessor->update($id);

    //dump($result);
    $dataAccessor = new SphinxDataAccess();
    $dataAccessor->updateFieldValue(10, 'rating', 7);

    dump(SphinxInstitutionAddress::find($id)->rating);


    // Частичные столбцы
    //$r = DBSphinx::table('institution_address')->where('id', $id)->update([
    //        'rating' => 11,
    //    ]);
    //dump($r, SphinxInstitutionAddress::find($id)->rating);
    /*
    $r = SphinxInstitutionAddress::where('id', 1)->update([
            'rating' => 22,
        ]);
    dump($r, SphinxInstitutionAddress::find(1)->rating);
    /**/
});


// Информация о таблицах
Route::get('/desc', function () {
    $fields = SphinxInstitutionAddress::getTableFields();
    dump($fields);

    $fields = SphinxProducts::getTableFields();
    dump($fields);
    return;
});

// Растояние
Route::get('/q', function (Request $request) {
    $result = SphinxInstitutionAddress::isOpen()->geodist(59.939767, 30.431271, 1000)->orderBy('isopen', 'desc')->get();

    dump($result->toArray());
});

// Выгрузка по институту
Route::get('/inst', function () {
    $institutionId = 1;
    $shardId = \App\Helpers\Institution::getPartnerIdByInstitutionId($institutionId);
    //$model = Institution::findShard($institutionId, $shardId);

    $record = new SphinxInstitutionAddress();
    $fields = $record->getTableFields();


    $model = Institution::byShardKey($shardId);
    /*
    //dump($model->newQuery());
    //return;
    $model = $model->newQuery()
        ->with(['addresses'])
        ->find($institutionId);
    //dump($model);
    //dump($model->addresses);
    dump($model);
    /**/
    /** @var Institution $institution */

    $institution = $model->newQuery()->with([
            'addresses.schedules',
            'institutionProperties',
            'institutionRelTag',
            'menus',
        ])->find($institutionId);

    dump($institution, $institution->institutionProperties);

    /** @var Properties $props */
    /** @var Collection $props */
    $props = $institution->institutionProperties->keyBy('property_id');

    $record->kitchen = $props->get(Properties::BMID_KITCHEN)['value_s'];
    $record->rating = $institution->getRating();
    $record->institution_id = $institution->id;
    $record->partner_id = $institution->partner_id;
    $record->is_network = $institution->is_network;

    $record->name = $institution->name;

    dump($record->toArray(), $props->toArray());

    dump($fields);

    //foreach ($institution->addresses as $address) {
    //    // $address->schedules
    //    $institution->institutionProperties
    //}

    //dump($model);
    //dump($model->addresses);
    //dump($model->toArray());


    //dump($fields);

    //$fatal_error = Institution::byShardKey($shardId)->with(['addresses'])->find($institutionId);
    //$good_model = Institution::byShardKey($shardId)->newQuery()->with(['addresses'])->find($institutionId);

    /*

    $dataProvider = new \App\Lib\Database\Sphinx\DataProvider($institutionId);
    $model = $dataProvider->getInstitution();

    dump($model);

    dump($dataProvider->getSchedules());
    dump($dataProvider->getAddresses());
    /**/
});

// Develop Test operation UPDATE / REPLACE / INSERT
Route::get('/qu', function () {
    $db = DBSphinx::getConnection();

    //$db->setQueryGrammar(new App\Lib\Database\Sphinx\Eloquent\Query\Grammar());
    //$db->query()->mergeBindings(new Builder())
    //dump($db->getSchemaBuilder());
    // dump($db->getQueryGrammar());
    // dump($db);

    $q = SphinxInstitutionAddress::query();
    //dump($q, get_class($q), $q->getQuery(), get_class($q->getQuery()));

    /** @var \App\Lib\Database\Sphinx\Eloquent\Builder $q */
    //$q->getQuery()->delete(6);
    $q = SphinxInstitutionAddress::query();
    dd($q->getQuery()->delete(78));

    //dump(DBSphinx::table('institution_address')->delete(6));
    //$r = DBSphinx::table('institution_address')->insert([
    $r = DBSphinx::table('institution_address')->replace([
        //$r = $q->replace([
        "id" => 78,
        "name" => "string",
        "kitchen" => "string",
        "inst_type" => "field",
        "products" => "field",
        //"rating" => "uint"
        //"institution_id" => "uint"
        //"partner_id" => "uint"
        //"min_price" => "uint"
        //"class_restaurant" => "uint"
        //"popularity" => "uint"
        //"delivery_time" => "uint"
        //"average_ticket_price" => "uint"
        //"weekdays" => "uint"
        //"city_id" => "uint"
        "latitude" => 12,
        "longitude" => 12,
        //"city_guid" => "string"
        "criterion" => $db->raw("(11,22,32,42)"),
        "tags" => $db->raw("(1,2,3,4)"),
        //"schedules" => "json"
    ]);
    dump($r);
    /**/
});

Route::get('/job', function () {
    dump(\Config::get('queue.default'));
    //return;
    $inst = 5;
    $shardId = \App\Helpers\Institution::getPartnerIdByInstitutionId($inst);
    $model = App\Models\Institution::findShard(5, $shardId);
    $model->name = substr('__' . time(), 10, 50);
    $model->save();
    dump('===========ok ok==========');
});


// Поиски
Route::get('/where', function (Request $request) {
    /* тест
    $result = SphinxInstitutionAddress::where('id', '=', 1001)
        ->isOpen()
        ->get()
        ->toArray();
    //dump($result);
    /**/

    // Работа с репозиторием
    $repository = \App::make(SphinxInstitutionAddressRepository::class);
    /** @var SphinxInstitutionAddressRepository $repository */
    $result = $repository->searchInstitutionsPaginate($request->all());
    //dump( $m =$result->getCollection()->first() );
    //print_r($m->open_through);
    //print_r($m->schedules);

    //dump($result->getCollection()->first()->open_through);

    dump($result->toArray());
    // ----------------------

    // Самостоятельная выборга
    //$result = SphinxInstitutionAddress::whereMath('тепло')->get();
    //dump($result);

    //$result = SphinxInstitutionAddress::wGroupWith('id')->get();
    //dump($result);

    //$result = SphinxInstitutionAddress::whereGroupWith('id')->get();
    //dump($result);

    //$m = new SphinxInstitutionAddress();
    //$result = $m->whereMath('тепло')->where('institution_id', '!=', 43)
    //    ->isOpen()
    //    //->withinGroupOrderBy('i')
    //    //->options('i', 8)
    //    ->get();
    //dump($result);
});

Route::get('/where-product', function (Request $request) {
    // Работа с репозиторием
    $repository = \App::make(SphinxProductsRepository::class);
    /** @var SphinxInstitutionAddressRepository $repository */
    $result = $repository->search($request->all());
    dump($result);
});


// Develop test
Route::get('/data', function (Request $request) {
    $dataAccessor = \App::make(SphinxDataAccess::class);
    /** @var SphinxDataAccess $dataAccessor */
    $result = $dataAccessor->update(1);

    //dump(\App\Helpers\InstitutionProperties::propertiesToArray($result));
    //dump($result->tags->pluck('tag_id')->implode(", "));
    //dump($result);

    dump(Dictionary::properties()->filter(function ($item) {
        return !empty($item['sphinx_filter']);
    })->keys()->toArray());

    //$properties = $result->properties;
    /** @var Collection $properties */

    //$kitchen = $properties->filter(function($item) {
    //    return $item->property_id == Properties::BMID_KITCHEN;
    //})->pluck('property_value_id');
    //dump($kitchen);
});

Route::get('/data-product', function (Request $request) {
    $dataAccessor = \App::make(ProductsDataAccess::class);
    /** @var ProductsDataAccess $dataAccessor */
    //$model = $dataAccessor->create(1094, 689);
    $model = $dataAccessor->create(101, 1);
    dump($model);

    dump('----------------');
    $model1 = Products::byShardKey(48)->newQuery()->with(['tags', 'ingredients'])->find(3125);
    dump($model1, '----------------');

    $model2 = $dataAccessor->create(4525, 556);
    dump($model2);

    $model1 = Products::byShardKey(689)->newQuery()->with(['tags', 'ingredients'])->find(1093);
    $model2 = $dataAccessor->create(463, 225);
    dump($model1, $model2);

});



Route::get('/group', function (Request $request) {
    // Работа с репозиторием
    //
    $repository = \App::make(SphinxInstitutionAddressRepository::class);
    $result = $repository->searchInstitutionsPaginate($request->all());

    //dump($result);
    //dump($result->getCollection());

    $collection = $result->getCollection();//->first();
    dump($collection->last()->institution);

    $collection->load('image');
    dump(get_class($collection), $collection->toArray());
    /// --------------


    /** @var SphinxProductsRepository $repository */
    $repository = \App::make(SphinxProductsRepository::class);
    $result = $repository->search($request->all());
    $result->load('product');
    dump(get_class($result), $result->toArray());
});
