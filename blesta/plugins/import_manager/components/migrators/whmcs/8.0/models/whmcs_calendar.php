<?php
/**
 *
 *
 */
class WhmcsCalendar
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select()->from('tblcalendar')->getStatement();
    }

    public function getTodos()
    {
        return $this->remote->select()->from('tbltodolist')->getStatement();
    }
}
