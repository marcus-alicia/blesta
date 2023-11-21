<?php
/**
 *
 *
 */
class WhmcsSupportTickets
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get()
    {
        $fields = ['tbltickets.*', 'tbladmins.id' => 'admin_id'];
        return $this->remote->select($fields)->from('tbltickets')->
            leftJoin('tbladmins', 'CONCAT_WS(?, tbladmins.firstname, tbladmins.lastname)', '=', 'tbltickets.admin', false)->
            appendValues([' '])->
            group(['tbltickets.id'])->
            getStatement();
    }

    public function getReplies()
    {
        $fields = ['tblticketreplies.*', 'tbladmins.id' => 'admin_id'];
        return $this->remote->select($fields)->from('tblticketreplies')->
            leftJoin('tbladmins', 'CONCAT_WS(?, tbladmins.firstname, tbladmins.lastname)', '=', 'tblticketreplies.admin', false)->
            appendValues([' '])->
            group(['tblticketreplies.id'])->
            getStatement();
    }

    public function getNotes()
    {
        $fields = ['tblticketnotes.*', 'tbladmins.id' => 'admin_id'];
        return $this->remote->select($fields)->from('tblticketnotes')->
            leftJoin('tbladmins', 'CONCAT_WS(?, tbladmins.firstname, tbladmins.lastname)', '=', 'tblticketnotes.admin', false)->
            appendValues([' '])->
            group(['tblticketnotes.id'])->
            getStatement();
    }

    public function getResponseCategories()
    {
        return $this->remote->select()->from('tblticketpredefinedcats')->order(['id' => 'ASC'])->getStatement();
    }

    public function getResponses()
    {
        return $this->remote->select()->from('tblticketpredefinedreplies')->getStatement();
    }
}
