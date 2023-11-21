<?php
namespace Minphp\Acl;

use Minphp\Record\Record;

/**
 * A generic Access Control List with inherited permissions based on a
 * hierarchical tree structure. The terminology for this system is as follows:
 *
 * ACL = Access Control List
 * ACO = Access Control Object (i.e. a thing, such as a URI)
 * ARO = Access Resource Object (i.e. an entity, such as a user)
 *
 * The ACL is made up of AROs and their associated ACOs for specific actions.
 * Actions are either explicitly allowed or denied.
 */
class Acl
{

    /**
     * @var Minphp\Record
     */
    protected $record;

    /**
     * Initialize the ACL
     */
    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    /**
     * Creates the ACL structure
     */
    public function create()
    {
        $this->record
            ->setField(
                'aro_id',
                array('type' => 'int', 'size' => 10)
            )
            ->setField(
                'aco_id',
                array('type' => 'int', 'size' => 10)
            )
            ->setField(
                'action',
                array('type' => 'varchar', 'size' => 255)
            )
            ->setField(
                'permission',
                array('type' => 'varchar', 'size' => 16)
            )
            ->setKey(array('aro_id', 'aco_id', 'action'), 'primary')
            ->create('acl_acl');

        $this->record
            ->setField(
                'id',
                array('type' => 'int', 'size' => 10, 'auto_increment' => true)
            )
            ->setField(
                'parent_id',
                array('type' => 'int', 'size' => 10)
            )
            ->setField(
                'alias',
                array('type' => 'varchar', 'size' => 255)
            )
            ->setField(
                'lineage',
                array('type' => 'varchar', 'size' => 255, 'default' => '/')
            )
            ->setKey(array('id'), 'primary')
            ->setKey(array('parent_id'), 'index')
            ->setKey(array('alias'), 'unique')
            ->create('acl_aro');

        $this->record
            ->setField(
                'id',
                array('type' => 'int', 'size' => 10, 'auto_increment' => true)
            )
            ->setField(
                'alias',
                array('type' => 'varchar', 'size' => 255)
            )
            ->setKey(array('id'), 'primary')
            ->setKey(array('alias'), 'unique')
            ->create('acl_aro');
    }

    /**
     * Check whether the ARO is allowed access to the ACO
     *
     * @param string $aro_alias The alias of the ARO
     * @param string $aco_alias The alias of the ACO
     * @param string $action The action to verify on the ACO
     * @return boolean True if the ARO is allowed to access the ACO, else false
     */
    public function check($aro_alias, $aco_alias, $action = '*')
    {
        $access_list = $this->getAccessList($aro_alias, $aco_alias);

        $list_size = count($access_list);

        // Holds the wildcard result for this ACO if defined
        $wildcard = false;

        for ($i=0; $i<$list_size; $i++) {
            // Asterisks (*) are considered wildcards
            if ($action === '*' || $access_list[$i]->action == $action) {
                if ($access_list[$i]->permission === 'allow') {
                    return true;
                }
                return false;
            }

            // If there is a wildcard action for this ACO use it in the case of no exact matches
            if ($access_list[$i]->action == '*') {
                $wildcard = $access_list[$i];
            }
        }

        // If there is a wildcard for this ACO use that value in the case where no better match found
        if ($wildcard && $wildcard->permission === 'allow') {
            return true;
        }

        return false;
    }

    /**
     * Fetches the Access List for the ARO on the given ACO
     *
     * @param string $aro_alias The alias of the ARO
     * @param string $aco_alias The alias of the ACO
     * @return array An array of ARO/ACO hierachy relationships
     */
    public function getAccessList($aro_alias, $aco_alias)
    {
        $aco = explode('/', $aco_alias);
        $access_list = array();

        // Attempt to find an entry for the given ACO, if no results, attempt for a subset of that ACO path
        $temp_aco = $aco_alias;
        $aco_count = count($aco);
        for ($i=0; $i<$aco_count; $i++) {
            // Build temp subquery
            $fields = array(
                'acl_aro.id',
                'acl_aro.alias',
                'acl_aro.lineage',
                'ancestor.id' => 'ancestor_id',
                'ancestor.alias' => 'ancestor_alias',
                'ancestor.lineage' => 'ancestor_lineage'
            );
            $temp = $this->record->select($fields)
                ->from('acl_aro')
                ->leftJoin(
                    array('acl_aro' => 'ancestor'),
                    'acl_aro.lineage',
                    'like',
                    "CONCAT('%/', ancestor.id, '/%')",
                    false,
                    false
                )
                ->where('acl_aro.alias', '=', $aro_alias);
            $temp_subquery = $temp->get();
            $values = $temp->values;
            $this->record->reset();
            $this->record->values = $values;

            // Build aro subquery (containing temp)
            $aro = $this->record->select(array('acl_aro.id', 'acl_aro.alias', 'acl_aro.lineage'))
                ->from('acl_aro')
                ->on('acl_aro.id', '=', 'temp.id', false)
                ->orOn('acl_aro.id', '=', 'temp.ancestor_id', false)
                ->innerJoin(array($temp_subquery=>'temp'))
                ->group('acl_aro.id')
                ->order(array('acl_aro.lineage'=>'desc'));
            $aro_subquery = $aro->get();
            $values = $aro->values;
            $this->record->reset();

            // Build query (containing aro subquery)
            $fields = array('aro.*', 'acl_acl.action', 'acl_acl.permission');
            $access_list = $this->record->select($fields)
                ->from('acl_acl')
                ->on('acl_acl.aco_id', '=', 'acl_aco.id', false)
                ->on('acl_aco.alias', '=', $temp_aco)
                ->innerJoin('acl_aco')
                ->innerJoin(array($aro_subquery=>'aro'), 'acl_acl.aro_id', '=', 'aro.id', false)
                ->appendValues($values)
                ->fetchAll();

            if ($access_list && !empty($access_list)) {
                break;
            }

            array_pop($aco);
            $temp_aco = implode('/', $aco);
        }

        return $access_list;
    }

    /**
     * Record that the ARO has access to the ACO for the given action
     *
     * @param string $aro_alias The alias of the ARO
     * @param string $aco_alias The alias of the ACO
     * @param string $action The action to allow
     */
    public function allow($aro_alias, $aco_alias, $action = '*')
    {
        $data = $this->getAroAcoByAlias($aro_alias, $aco_alias);
        if ($data) {
            $this->addAcl($data->aro_id, $data->aco_id, $action, 'allow');
        }
    }

    /**
     * Record that the ARO does not have access to the ACO for the given action
     *
     * @param string $aro_alias The alias of the ARO
     * @param string $aco_alias The alias of the ACO
     * @param string $action The action to deny
     */
    public function deny($aro_alias, $aco_alias, $action = '*')
    {
        $data = $this->getAroAcoByAlias($aro_alias, $aco_alias);
        if ($data) {
            $this->addAcl($data->aro_id, $data->aco_id, $action, 'deny');
        }
    }

    /**
     * Add a new ARO as a child to the given parent
     *
     * @param string $alias The alias of the ARO
     * @param mixed $parent The parent of this ARO, either the int ID, or a string alias of the parent ARO
     * @return int The ID of the ARO added
     */
    public function addAro($alias, $parent = null)
    {
        $lineage = '/';
        if ($parent != null && !is_numeric($parent)) {
            $aro = $this->getAroByAlias($parent);
            if ($aro) {
                $parent = $aro->id;
                $lineage = $aro->lineage . $parent . '/';
            } else {
                $parent = null;
            }
        }

        $this->record->set('parent_id', $parent)
            ->set('alias', $alias)
            ->set('lineage', $lineage)
            ->insert('acl_aro');
        return $this->record->lastInsertId();
    }

    /**
     * Removes the ARO from the ARO and ACL
     *
     * @param string $alias The Alias of the ARO
     */
    public function removeAro($alias)
    {
        // Remove the record from the ARO and ACL
        $this->record->from('acl_aro')
            ->leftJoin('acl_acl', 'acl_acl.aro_id', '=', 'acl_aro.id', false)
            ->where('acl_aro.alias', '=', $alias)
            ->delete(array('acl_aro.*', 'acl_acl.*'));
    }

    /**
     * Add a new ACO
     *
     * @param string $alias The alias of the ACO
     * @return int The ID of the ACO added
     */
    public function addAco($alias)
    {
        $this->record->set('alias', $alias)
            ->insert('acl_aco');
        return $this->record->lastInsertId();
    }

    /**
     * Removes the ACO from the ACO and ACL
     *
     * @param string $alias The Alias of the ACO
     */
    public function removeAco($alias)
    {
        // Remove the record from the ACO and ACL
        $this->record->from('acl_aco')
            ->leftJoin('acl_acl', 'acl_acl.aco_id', '=', 'acl_aco.id', false)
            ->where('acl_aco.alias', '=', $alias)
            ->delete(array('acl_aco.*', 'acl_acl.*'));
    }

    /**
     * Removes an entry from the ACL that matches the given ARO, ACO, and action
     *
     * @param string $aro_alias The ARO alias
     * @param string $aco_alias The ACO alias
     * @param string $action The action
     */
    public function removeAcl($aro_alias = null, $aco_alias = null, $action = null)
    {

        // Only allow delete if at least ARO or ACO is given
        if ($aro_alias == null && $aco_alias == null) {
            return;
        }

        $this->record->from('acl_aro')
            ->from('acl_aco')
            ->from('acl_acl')
            ->where('acl_acl.aro_id', '=', 'acl_aro.id', false)
            ->where('acl_acl.aco_id', '=', 'acl_aco.id', false);

        if ($aro_alias != null) {
            $this->record->where('acl_aro.alias', '=', $aro_alias);
        }
        if ($aco_alias != null) {
            $this->record->where('acl_aco.alias', '=', $aco_alias);
        }
        if ($action != null) {
            $this->record->where('acl_acl.action', '=', $action);
        }

        $this->record->delete(array('acl_acl.*'));
    }

    /**
     * Retrieve the ARO and ACO using the given ARO and ACO aliases
     *
     * @param string $aro_alias The ARO alias
     * @param string $aco_alias The ACO alias
     * @return mixed An array of the ARO/ACO combo, false otherwise
     */
    private function getAroAcoByAlias($aro_alias, $aco_alias)
    {
        $fields = array(
            'acl_aro.id' => 'aro_id',
            'acl_aro.lineage' => 'aro_lineage',
            'acl_aco.id' => 'aco_id'
        );
        return $this->record->select($fields)
            ->from('acl_aro')
            ->from('acl_aco')
            ->where('acl_aro.alias', '=', $aro_alias)
            ->where('acl_aco.alias', '=', $aco_alias)
            ->fetch();
    }

    /**
     * Retrieve the ARO with the given alias
     *
     * @param string $alias The alias of the ARO
     * @return mixed An array containing the ARO, false if no match found
     */
    public function getAroByAlias($alias)
    {
        $fields = array('id', 'parent_id', 'alias', 'lineage');
        return $this->record->select($fields)
            ->from('acl_aro')
            ->where('alias', '=', $alias)
            ->fetch();
    }

    /**
     * Retrieve the ACO with the given alias
     *
     * @param string $alias The alias of the ACO
     * @return mixed An array containing the ACO, false if no match found
     */
    public function getAcoByAlias($alias)
    {
        $fields = array('id', 'alias');
        return $this->record->select($fields)
            ->from('acl_aco')
            ->where('alias', '=', $alias)
            ->fetch();
    }

    /**
     * Record the given ARO and ACO IDs for the action and permission given
     *
     * @param int $aro_id The ARO ID
     * @param int $aco_id The ACO ID
     * @param string $action The action to allow or deny
     * @param string $permission 'allow' or 'deny'
     */
    private function addAcl($aro_id, $aco_id, $action, $permission)
    {
        $vars = array('aro_id' => $aro_id, 'aco_id' => $aco_id, 'action' => $action, 'permission' => $permission);
        $this->record->duplicate('permission', '=', $permission)
            ->insert('acl_acl', $vars);
    }
}
