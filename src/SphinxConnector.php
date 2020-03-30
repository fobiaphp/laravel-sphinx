<?php
/**
 * SphinxConnector.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Illuminate\Database\Connectors\MySqlConnector;

/**
 * Class SphinxConnector
 * @ignore
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class SphinxConnector extends MySqlConnector
{
    /**
     * @internal
     */
    public function connect(array $config)
    {
        $defaultConfig = [
            'host' => '127.0.0.1',
            'port' => 9306,
            'database' => '', // null,
            'username' => '',
            // 'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'collation' => null,
        ];

        $config = array_merge($defaultConfig, $config);

        return parent::connect($config);
    }
}
