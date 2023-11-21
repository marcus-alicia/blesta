<?php

use Minphp\Db\PdoConnection;
use Minphp\Bridge\Initializer;

/**
 * Model Bridge
 */
class Model extends PdoConnection
{
    /**
     * Initialize
     */
    public function __construct(array $dbInfo = null)
    {
        $container = Initializer::get()->getContainer();

        if (null === $dbInfo) {
            $dbInfo = [];
        }

        parent::__construct($dbInfo);

        if (empty($dbInfo)) {
            $this->setConnection($container->get('pdo'));
        }
    }
}
