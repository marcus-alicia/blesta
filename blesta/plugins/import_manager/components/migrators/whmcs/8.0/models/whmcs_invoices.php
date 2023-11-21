<?php
/**
 *
 *
 */
class WhmcsInvoices
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select(['tblinvoices.*', 'tblcurrencies.code' => 'currency'])->from('tblinvoices')->
            innerJoin('tblclients', 'tblclients.id', '=', 'tblinvoices.userid', false)->
            innerJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            getStatement();
    }

    public function getLines()
    {
        return $this->remote->select()->from('tblinvoiceitems')->
            getStatement();
    }

    public function getRecurringLines()
    {
        return $this->remote->select(['tblbillableitems.*', 'tblcurrencies.code' => 'currency'])->
            from('tblbillableitems')->
            innerJoin('tblclients', 'tblclients.id', '=', 'tblbillableitems.userid', false)->
            innerJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            where('tblbillableitems.invoiceaction', '=', 4)->getStatement();
    }

    public function getRecurInstances($item_id)
    {
        return $this->remote->select()->from('tblinvoiceitems')->
            where('relid', '=', $item_id)->
            where('type', '=', 'Item')->getStatement();
    }
}
