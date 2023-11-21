<?php
/**
 *
 *
 */
class WhmcsAffiliates
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select(['tblaffiliates.*', 'tblcurrencies.code' => 'currency_code'])->
            from('tblaffiliates')->
            innerJoin('tblclients', 'tblclients.id', '=', 'tblaffiliates.clientid', false)->
            leftJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            getStatement();
    }

    public function getSales($affiliate_id)
    {
        return $this->remote->select()->
            from('tblaffiliatesaccounts')->
            where('tblaffiliatesaccounts.affiliateid', '=', $affiliate_id)->
            fetchAll();
    }

    public function getSalesCount($affiliate_id)
    {
        return $this->remote->select()->
            from('tblaffiliatesaccounts')->
            where('tblaffiliatesaccounts.affiliateid', '=', $affiliate_id)->
            numResults();
    }

    public function getPending($affiliate_id)
    {
        return $this->remote->select([
            'tblaffiliatespending.*',
            'tblaffiliatesaccounts.*',
            'tblaffiliates.*',
            'tblhosting.packageid',
            'tblhosting.amount',
            'tblclients.firstname',
            'tblclients.lastname',
            'tblaffiliatespending.id' => 'pending_id',
            'tblaffiliatesaccounts.id' => 'account_id',
            'tblaffiliates.id' => 'affiliate_id',
            'tblaffiliatespending.amount' => 'commission',
            'tblcurrencies.code' => 'currency_code'
        ])->
            from('tblaffiliatespending')->
            innerJoin('tblaffiliatesaccounts', 'tblaffiliatesaccounts.id', '=', 'tblaffiliatespending.affaccid', false)->
            innerJoin('tblaffiliates', 'tblaffiliates.id', '=', 'tblaffiliatesaccounts.affiliateid', false)->
            leftJoin('tblhosting', 'tblhosting.id', '=', 'tblaffiliatesaccounts.relid', false)->
            leftJoin('tblclients', 'tblclients.id', '=', 'tblhosting.userid', false)->
            leftJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            where('tblaffiliatesaccounts.affiliateid', '=', $affiliate_id)->
            getStatement();
    }

    public function getWithdrawals($affiliate_id)
    {
        return $this->remote->select()->
            from('tblaffiliateswithdrawals')->
            where('tblaffiliateswithdrawals.affiliateid', '=', $affiliate_id)->
            getStatement();
    }
}
