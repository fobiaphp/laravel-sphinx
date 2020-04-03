<?php
/**
 * Connection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Foolz\SphinxQL\Drivers\Pdo\Connection as ConnectionBase;

/**
 * Class Connection
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2020 Dmitriy Tyurin
 */
class SphinxQLDriverConnection extends ConnectionBase
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

    public function connect()
    {
        return !empty($this->connection);
    }
}
