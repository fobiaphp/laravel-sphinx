<?php
/**
 * Connection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Foolz\SphinxQL\Drivers\Pdo\Connection as ConnectionBase;

/**
 * Class Connection
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class SphinxQLDriversConnection extends ConnectionBase
{

    /**
     * Connection constructor.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->connection = $pdo;
    }


    /*******************
     * Override Methods
     *******************/


    public function connect($suppress_error = false)
    {
        return !empty($this->connection);
    }
}
