<?php
namespace Minphp\Db\Tests;

use PDO;

class MockablePdo extends PDO
{
    public function __construct()
    {
    }
}
