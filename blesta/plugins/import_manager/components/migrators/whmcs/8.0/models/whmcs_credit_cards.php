<?php
/**
 *
 *
 */
class WhmcsCreditcards
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select([
            'tblcreditcards.*',
            'tblpaymethods.gateway_name',
            'tblpaymethods.payment_type',
            'tblclients.id' => 'client_id',
            'tblclients.firstname',
            'tblclients.lastname',
            'tblclients.address1',
            'tblclients.address2',
            'tblclients.city',
            'tblclients.state',
            'tblclients.postcode',
            'tblclients.country'
        ])->
            from('tblcreditcards')->
            innerJoin('tblpaymethods', 'tblpaymethods.payment_id', '=', 'tblcreditcards.id', false)->
            innerJoin('tblclients', 'tblclients.id', '=', 'tblpaymethods.userid', false)->
            getStatement();
    }
}
