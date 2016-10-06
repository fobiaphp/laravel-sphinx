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
class ProductModel extends BaseModel
{

    protected $table = 'products';

    protected $fillable = [
        'id',
        'name',
        'institution_id',
        'partner_id',
        'menu_id',
        'tags',
        'factors'
    ];

    protected $guarded = ['*'];

    protected $appends = [ ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'tags' => 'mva',
        'factors' => 'json',
    ];
}
