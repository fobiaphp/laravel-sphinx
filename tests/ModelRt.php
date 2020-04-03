<?php
/**
 * Model.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection\Test;

use Fobia\Database\SphinxConnection\Eloquent\Model as BaseModel;

/**
 * Class Model
 *
 * @property int $id                Institution address_id
 * @property string $name               Institution name
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class ModelRt extends BaseModel
{
    protected $table = 'rt';

    protected $fillable = [
        'id',
        'name',
        'content',
        'gid',
        'greal',
        'gbool',
        'tags',
        'factors',
    ];

    protected $guarded = ['*'];

    protected $appends = [];

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
