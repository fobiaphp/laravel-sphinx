<?php
/**
 * Model.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test;

use Fobia\Database\SphinxConnection\Eloquent\Model as BaseModel;


/**
 * Class Model
 *
 * @property integer $id                Institution address_id
 * @property string $name               Institution name
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class Model extends BaseModel
{

    protected $table = 'products';

    protected $fillable = [];

    protected $guarded = ['*'];

    protected $appends = [
        'address_id',
        'open_through',
        'image_url',
        'image_small',
        'dictionary_kitchens',
        'dummy_image',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'schedules' => 'json',
        //'tags' => 'array',
        //'criterions' => 'array',
        //'kitchens' => 'array',
        'factors' => 'json',
    ];
}
