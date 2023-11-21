<?php

/**
 * Module Client Meta
 *
 * Allows read/write data containing module or module row specific data for a
 * particular client.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ModuleClientMeta extends AppModel
{
    /**
     * Add or update module meta data for a particular client and module
     *
     * @param int $client_id The ID of the client to set data for
     * @param int $module_id The ID of the module
     * @param int $module_row_id The ID of the module row (if necessary)
     * @param array $fields A numerically indexed array of arrays, each containing:
     *
     *  - key The key to set
     *  - value The value to set
     *  - encrypted True to encrypt $value, false to store unencrypted, null
     *      to encrypt if currently set to encrypt (default null)
     */
    public function set($client_id, $module_id, $module_row_id = 0, array $fields = [])
    {
        $cols = ['client_id', 'module_id', 'module_row_id', 'key', 'value', 'serialized', 'encrypted'];
        foreach ($fields as $field) {
            $vars = compact('client_id', 'module_id', 'module_row_id');
            $vars['key'] = null;
            $vars['value'] = null;
            $vars['serialized'] = 0;
            $vars['encrypted'] = 0;

            if (isset($field['key'])) {
                $vars['key'] = $field['key'];
            }
            if (isset($field['value'])) {
                $vars['value'] = $field['value'];
            }
            // Serialize value for storage
            if (!is_scalar($vars['value'])) {
                $vars['value'] = serialize($field['value']);
                $vars['serialized'] = 1;
            }
            // Encrypt?
            if ((!array_key_exists('encrypted', $field) || $field['encrypted'] === null)
                && ($meta = $this->get($client_id, $vars['key'], $module_id, $module_row_id))
            ) {
                $field['encrypted'] = $meta->encrypted;
            }

            if (isset($field['encrypted']) && $field['encrypted']) {
                $vars['value'] = $this->systemEncrypt($vars['value']);
                $vars['encrypted'] = 1;
            }

            $this->Record->duplicate('value', '=', $vars['value'])->
                duplicate('serialized', '=', $vars['serialized'])->
                duplicate('encrypted', '=', $vars['encrypted'])->
                insert('module_client_meta', $vars, $cols);
        }
    }

    /**
     * Fetch a specific meta field
     *
     * @param int $client_id The ID of the client to fetch on
     * @param string $key The key to fetch
     * @param int $module_id The ID of the module
     * @param int $module_row_id The ID of the module row (if necessary)
     * @return mixed A stdClass object representing the meta field, false if no such field exists
     */
    public function get($client_id, $key, $module_id, $module_row_id = 0)
    {
        $result = $this->Record->select()->from('module_client_meta')->
            where('module_client_meta.client_id', '=', $client_id)->
            where('module_client_meta.key', '=', $key)->
            where('module_client_meta.module_id', '=', $module_id)->
            where('module_client_meta.module_row_id', '=', $module_row_id)->
            fetch();

        if ($result) {
            if ($result->encrypted) {
                $result->value = $this->systemDecrypt($result->value);
            }
            if ($result->serialized) {
                $result->value = unserialize($result->value);
            }
        }

        return $result;
    }

    /**
     * Fetches all meta fields for a client
     *
     * @param int $client_id The ID of the client to fetch on
     * @param int $module_id The ID of the module
     * @param int $module_row_id The ID of the module row (if necessary)
     * @return array An array of stdClass objects, each representing a meta field
     */
    public function getAll($client_id, $module_id, $module_row_id = 0)
    {
        $results = $this->Record->select()->from('module_client_meta')->
            where('module_client_meta.client_id', '=', $client_id)->
            where('module_client_meta.module_id', '=', $module_id)->
            where('module_client_meta.module_row_id', '=', $module_row_id)->
            fetchAll();

        foreach ($results as $result) {
            if ($result->encrypted) {
                $result->value = $this->systemDecrypt($result->value);
            }
            if ($result->serialized) {
                $result->value = unserialize($result->value);
            }
        }

        return $results;
    }

    /**
     * Delete a specific entry for a client and module
     *
     * @param int $client_id The ID of the client
     * @param string $key The key to delete
     * @param int $module_id The ID of the module
     * @param int $module_row_id The ID of the module row (if necessary)
     */
    public function delete($client_id, $key, $module_id, $module_row_id = 0)
    {
        $this->Record->from('module_client_meta')->
            where('module_client_meta.client_id', '=', $client_id)->
            where('module_client_meta.key', '=', $key)->
            where('module_client_meta.module_id', '=', $module_id)->
            where('module_client_meta.module_row_id', '=', $module_row_id)->
            delete();
    }

    /**
     * Deletes all meta fields for a client and module
     *
     * @param int $client_id The ID of the client
     * @param int $module_id The ID of the module
     * @param int $module_row_id The ID of the module row (if necessary)
     */
    public function deleteAll($client_id, $module_id, $module_row_id = 0)
    {
        $this->Record->from('module_client_meta')->
            where('module_client_meta.client_id', '=', $client_id)->
            where('module_client_meta.module_id', '=', $module_id)->
            where('module_client_meta.module_row_id', '=', $module_row_id)->
            delete();
    }
}
