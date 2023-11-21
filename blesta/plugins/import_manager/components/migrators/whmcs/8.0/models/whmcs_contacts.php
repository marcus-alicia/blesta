<?php
/**
 *
 *
 */
class WhmcsContacts
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select()->from('tblcontacts')->getStatement();
    }
}
