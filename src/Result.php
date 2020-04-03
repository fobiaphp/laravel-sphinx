<?php
/**
 * Result.php file
 */

namespace Fobia\Database\SphinxConnection;

use Illuminate\Support\Collection;

/**
 * Class Result
 */
class Result
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $result;

    /**
     * @var int
     */
    public $total;

    public function __construct($result = null)
    {
        $this->result = new Collection($result ?? []);
        $this->total = 0;
    }
}
