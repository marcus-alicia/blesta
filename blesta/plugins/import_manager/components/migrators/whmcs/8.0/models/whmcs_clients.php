<?php
/**
 *
 *
 */
class WhmcsClients
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        return $this->remote->select([
                'tblclients.*',
                'tblusers.email' => 'user_email',
                'tblusers.password',
                'tblcurrencies.code' => 'currency_code'
            ])->
            from('tblclients')->
            leftJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            on('tblusers_clients.owner', '=', 1)->
            leftJoin('tblusers_clients', 'tblusers_clients.client_id', '=', 'tblclients.id', false)->
            leftJoin('tblusers', 'tblusers.id', '=', 'tblusers_clients.auth_user_id', false)->
            group('tblclients.id')->
            getStatement();
    }

    public function getGroups()
    {
        return $this->remote->select()->from('tblclientgroups')->getStatement();
    }

    public function getCustomFields()
    {
        return $this->remote->select()->from('tblcustomfields')->where('type', '=', 'client')->
            order(['sortorder' => 'ASC'])->getStatement();
    }

    public function getCustomFieldValues($field_id)
    {
        return $this->remote->select(['tblcustomfieldsvalues.*', 'tblclients.groupid'])->from('tblcustomfieldsvalues')->
            innerJoin('tblclients', 'tblclients.id', '=', 'tblcustomfieldsvalues.relid', false)->
            where('tblcustomfieldsvalues.fieldid', '=', $field_id)->
            getStatement();
    }

    public function getNotes()
    {
        return $this->remote->select()->from('tblnotes')->getStatement();
    }
}
