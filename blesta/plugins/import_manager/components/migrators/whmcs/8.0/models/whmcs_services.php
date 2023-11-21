<?php
/**
 *
 *
 */
class WhmcsServices
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Fetch all standard services
     *
     * @return PDOStatement
     */
    public function get()
    {
        return $this->remote->select()->from('tblhosting')->getStatement();
    }

    /**
     * Fetch all addon services
     *
     * @return PDOStatement
     */
    public function getAddons()
    {
        return $this->remote->select()->from('tblhostingaddons')->getStatement();
    }

    /**
     * Fetch all config options
     *
     * @return PDOStatement
     */
    public function getConfigOptions()
    {
        return $this->remote->select(['tblhostingconfigoptions.*', 'tblhosting.userid', 'tblhosting.billingcycle', 'tblproductconfigoptions.optiontype'])->
            from('tblhostingconfigoptions')->
            innerJoin('tblhosting', 'tblhosting.id', '=', 'tblhostingconfigoptions.relid', false)->
            innerJoin('tblproductconfigoptions', 'tblproductconfigoptions.id', '=', 'tblhostingconfigoptions.configid', false)->
            getStatement();
    }

    /**
     * Fetch all custom fields for an specific service
     *
     * @param int $relid The ID of the service
     * @return array An array containing the custom fields for the service
     */
    public function getCustomFields($relid)
    {
        $fields = $this->remote->select(['tblcustomfields.fieldname', 'tblcustomfieldsvalues.value'])->
            from('tblcustomfields')->
            innerJoin('tblcustomfieldsvalues', 'tblcustomfieldsvalues.fieldid', '=', 'tblcustomfields.id', false)->
            where('tblcustomfields.type', '=', 'product')->
            where('tblcustomfieldsvalues.relid', '=', $relid)->
            fetchAll();

        $custom_fields = [];
        foreach ($fields as $field) {
            $custom_fields[$field->fieldname] = $field->value;
        }

        return $custom_fields;
    }

    /**
     * Fetch all domain-name services
     *
     * @return PDOStatement
     */
    public function getDomains()
    {
        return $this->remote->select()->from('tbldomains')->getStatement();
    }

    /**
     * Coverts term name into actual term/period
     *
     * @param mixed $term_name The term name (e.g. "Monthly", "Semi-Annually", etc.), or an integer representing the number of years
     * @return array An array of key/value pairs including:
     * 	- term The term
     * 	- period The period
     */
    public function getTerm($term_name)
    {
        if (is_numeric($term_name)) {
            return ['term' => $term_name, 'period' => 'year'];
        }

        switch ($term_name) {
            default:
            case 'Free Account':
            case 'One Time':
                return ['term' => 0, 'period' => 'onetime'];
            case 'Monthly':
                return ['term' => 1, 'period' => 'month'];
            case 'Quarterly':
                return ['term' => 3, 'period' => 'month'];
            case 'Semi-Annually':
                return ['term' => 6, 'period' => 'month'];
            case 'Annually':
                return ['term' => 1, 'period' => 'year'];
            case 'Biennially':
                return ['term' => 2, 'period' => 'year'];
            case 'Triennially':
                return ['term' => 3, 'period' => 'year'];
        }
    }
}
