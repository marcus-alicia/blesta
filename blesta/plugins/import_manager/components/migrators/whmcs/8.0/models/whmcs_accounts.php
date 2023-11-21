<?php
/**
 *
 *
 */
class WhmcsAccounts
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get($with_refund = false)
    {
        $subquery = null;
        if ($with_refund) {
            /*
            SELECT tblaccounts.*, trans_currencies.code AS trans_currency, client_currencies.code AS client_currency, refunds.refund FROM tblaccounts
            LEFT JOIN (SELECT SUM(amountout) AS refund, ref.refundid FROM tblaccounts AS ref WHERE ref.refundid > 0 GROUP BY ref.refundid) AS refunds ON refunds.refundid=tblaccounts.id
            LEFT JOIN tblcurrencies AS trans_currencies ON trans_currencies.id=tblaccounts.currency
            LEFT JOIN tblclients ON tblclients.id=tblaccounts.userid
            LEFT JOIN tblcurrencies AS client_currencies ON client_currencies.id=tblclients.currency
            */

            $subquery = $this->remote->select(['SUM(ref.amountout)' => 'refund', 'ref.refundid'])->
                from(['tblaccounts' => 'ref'])->where('ref.refundid', '>', 0)->
                group(['ref.refundid'])->get();

            $values = $this->remote->values;
            $this->remote->reset();
        }

        $fields = ['tblaccounts.*', 'trans_currencies.code' => 'trans_currency', 'client_currencies.code' => 'client_currency'];
        if ($with_refund) {
            $fields[] = 'refunds.refund';
        }

        $this->remote->select($fields)->from('tblaccounts')->
            leftJoin(['tblcurrencies' => 'trans_currencies'], 'trans_currencies.id', '=', 'tblaccounts.currency', false);

        if ($with_refund) {
            $this->remote->appendValues($values)->
            leftJoin([$subquery => 'refunds'], 'refunds.refundid', '=', 'tblaccounts.id', false);
        }

        $this->remote->leftJoin('tblclients', 'tblclients.id', '=', 'tblaccounts.userid', false)->
            leftJoin(['tblcurrencies' => 'client_currencies'], 'client_currencies.id', '=', 'tblclients.currency', false);

        return $this->remote->getStatement();
    }

    public function getOpenCredits()
    {
        $fields = ['tblclients.id' => 'userid', 'tblclients.credit', 'tblcurrencies.code' => 'currency'];
        return $this->remote->select($fields)->from('tblclients')->
            leftJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            where('tblclients.credit', '>', 0)->getStatement();
    }

    public function getCredits()
    {
        $fields = ['tblclients.id' => 'userid', 'tblclients.credit', 'tblcurrencies.code' => 'currency'];
        return $this->remote->select($fields)->from('tblclients')->
            leftJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            where('tblclients.credit', '>=', 0)->getStatement();
    }
}
