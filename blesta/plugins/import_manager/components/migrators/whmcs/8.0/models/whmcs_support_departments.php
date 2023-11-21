<?php
/**
 *
 *
 */
class WhmcsSupportDepartments
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select()->from('tblticketdepartments')->getStatement();
    }
}
