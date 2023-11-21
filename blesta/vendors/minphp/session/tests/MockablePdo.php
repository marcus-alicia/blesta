<?php
namespace Minphp\Session\Tests;

use PDO;

class MockablePdo extends PDO
{
    public function __construct()
    {
    }
}
