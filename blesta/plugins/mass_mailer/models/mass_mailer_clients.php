<?php

use Blesta\MassMailer\Traits\Filters;

/**
 * MassMailerClients model
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.models
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerClients extends MassMailerModel
{
    use Filters;

    /**
     * Set fields to select by default
     */
    public function __construct()
    {
        parent::__construct();

        // Set default query options
        $this->setClientFields(['contacts.*', 'clients.status']);
        // Set comma specifically, as appending the value would do so in the wrong order
        $this->setServiceFields(
            ['GROUP_CONCAT(services.id SEPARATOR \',\')' => 'service_ids'],
            false
        );
        $this->setGroupBy(['contacts.id']);
    }

    /**
     * Retrieves a PDOStatement for fetching all matching results for
     * the given filters
     *
     * @param array $filters An array of filtering options
     * @return PDOStatement The executed statement object to be iterated over
     */
    public function getAll(array $filters)
    {
        $this->setFilters($filters);
        return $this->filter($this->Record)->getStatement();
    }

    /**
     * Retrieves the total number of matching results for the given
     * filters
     *
     * @param array $filters An array of filtering options
     * @return int The total number of matching contacts
     */
    public function getAllCount(array $filters)
    {
        $this->setFilters($filters);
        return $this->filter($this->Record)->numResults();
    }
}
