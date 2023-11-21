<?php
/**
 *
 *
 */
class WhmcsCurrencies
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select()->from('tblcurrencies')->getStatement();
    }

    public function getDefaultCode()
    {
        $currency = null;
        $result = $this->remote->select()->from('tblcurrencies')->
            where('default', '=', 1)->fetch();

        if ($result) {
            $currency = $result->code;
        }
        return $currency;
    }
}
