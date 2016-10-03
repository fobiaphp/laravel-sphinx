<?php
/**
 * Connection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Foolz\SphinxQL\Drivers\Pdo\Connection as ConnectionBase;
use Debugbar;

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
     *     array(
     *       'host' => '127.0.0.1',
     *       'port' => 9306,
     *       'socket' => null
     *     );
     *
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->connection = $pdo;
    }

    /**
     * Creates and setups a SphinxQL object
     *
     * @return \Foolz\SphinxQL\SphinxQL
    // */
    //public function createSphinxQL()
    //{
    //    return SphinxQL::create($this);
    //}
    
    /*******************
     * Override Methods
     *******************/


    public function connect($suppress_error = false)
    {
        return !empty($this->connection);
        /*
        try {
            $db = Sphinx::getConnection();
            if (!$db) {
                throw new \Exception("Sphinx connection not init");
            }
            $pdo = $db->getPdo();
            $this->connection = $pdo;

            Debugbar::addMessage('Sphinx connection');
        } catch (\Exception $e) {
            // TODO: обработать
            throw $e;
        }

        return true; /**/
    }

    /*
    public function query($query)
    {
        // Логируем время выполнения запроса
        $t = microtime(true);
        $result = parent::query($query);
        $t = round((microtime(true) - $t) * 1000, 2); // Результат в милисекундах, т.е. x10^(-3)
        Debugbar::addMessage("Time ${t}ms: " . $query, 'Sphinx');

        return $result;
    } /**/

    /*
    public function multiQuery(array $queue)
    {
        foreach ($queue as $q) {
            Debugbar::addMessage($q, 'Sphinx');
        }
        return parent::multiQuery($queue);
    } /**/
    
    /**
     * Логируем время выполнения запроса
     *
     * @param $message         сообщение
     * @param null|float $time стартовое время выполнения
     */
    public function addMessageDebugbar($message, $time = null)
    {
        if ($time) {
            $time = round((microtime(true) - $time) * 1000, 2); // Результат в милисекундах, т.е. x10^(-3)
            $time = "Time ${$time}ms: ";
        }
        Debugbar::addMessage((string) $time . $message, 'Sphinx');
    }

}
