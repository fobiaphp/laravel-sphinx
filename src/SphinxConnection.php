<?php
/**
 * SphinxConnection.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */

namespace Fobia\Database\SphinxConnection;

use Foolz\SphinxQL\SphinxQL;
use Illuminate\Database\MySqlConnection;


/**
 * Class SphinxConnection
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2016 Dmitriy Tyurin
 */
class SphinxConnection extends MySqlConnection
{
    /**
     * @var \Fobia\Database\SphinxConnection\SphinxQLDriversConnection
     */
    protected $sphinxQLConnection;
    
    /**
     * @inheritDoc
     */
    public function __construct($pdo, $database, $tablePrefix, array $config)
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
    }
    
    /**
     * @return \Fobia\Database\SphinxConnection\SphinxQLDriversConnection
     */
    public function getSphinxQLDriversConnection()
    {
        if (null === $this->sphinxQLConnection) {
            $this->sphinxQLConnection = new SphinxQLDriversConnection($this->getPdo());
        }
        return $this->sphinxQLConnection;
    }
    
    /**
     * @return \Foolz\SphinxQL\SphinxQL
     */
    public function createSphinxQL()
    {
        return SphinxQL::create($this->getSphinxQLDriversConnection());
    }
}
