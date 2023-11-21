<?php
/**
 *
 *
 */
class WhmcsPaymentGateways
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select(['tblpaymentgateways.*'])->
            from('tblpaymentgateways')->
            getStatement();
    }
}
