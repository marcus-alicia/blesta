<?php

use Minphp\Bridge\Initializer;
use Minphp\Record\Record as MinphpRecord;

/**
 * Record Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Record\Record instead.
 */
class Record extends MinphpRecord
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

        // Get database info from the configuration if available
        $configDbInfo = Configure::get('Database.profile');

        // Check if the connection is communicating with utf8mb4
        $isMb4 = is_array($configDbInfo)
            && isset($configDbInfo['charset_query'])
            && strpos($configDbInfo['charset_query'], 'utf8mb4');

        // Default new table collation/character set to utf8 if not using utf8mb4
        if (!$isMb4) {
            $this->setCharacterSet('utf8');
            $this->setCollation('utf8_unicode_ci');
        }

        if (empty($dbInfo)) {
            $this->setConnection($container->get('pdo'));
        }
    }
}
