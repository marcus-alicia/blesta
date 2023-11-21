<?php

use Minphp\Bridge\Initializer;
use Minphp\Record\Record;
use Minphp\Acl\Acl as MinphpAcl;

/**
 * ACL Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Acl\Acl instead.
 */
class Acl extends MinphpAcl
{
    /**
     * Initialize
     */
    public function __construct()
    {
        $container = Initializer::get()->getContainer();
        $record = new Record([]);
        $record->setConnection($container->get('pdo'));
        parent::__construct($record);
    }
}
