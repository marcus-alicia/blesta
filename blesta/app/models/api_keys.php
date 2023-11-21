<?php

use phpseclib\Crypt\Random;

/**
 * API Key management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ApiKeys extends AppModel
{
    /**
     * Initialize the API Keys
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['api_keys']);
    }

    /**
     * Authenticates the given credentials and returns the company ID the API user
     * has access to.
     *
     * @param string $user The API user
     * @param string $key The API user's key
     * @return int The ID of the company the user belongs to, void if the
     *  credentials are invalid. Raises Input::errors() on error.
     */
    public function auth($user, $key)
    {
        $result = $this->Record->select(['company_id'])
            ->from('api_keys')
            ->where('user', '=', $user)
            ->where('key', '=', $key)
            ->fetch();

        if ($result) {
            return $result->company_id;
        } else {
            $this->Input->setErrors([
                'user' => [
                    'valid' => $this->_('ApiKeys.!error.user.valid')
                ]
            ]);
        }
    }

    /**
     * Returns a list of API keys
     *
     * @param int $page The page to fetch results on
     * @param array $order_by $order_by The sort and order conditions (e.g. ['sort_field' => "ASC"])
     * @return array An array of stdClass objects
     */
    public function getList($page = 1, $order_by = ['date_created' => 'desc'])
    {
        $this->Record = $this->keys();

        return $this->Record->order($order_by)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Returns a count of the number of results in a list of API keys
     *
     * @return int The number of results in a list of API keys
     */
    public function getListCount()
    {
        $this->Record = $this->keys();

        return $this->Record->numResults();
    }

    /**
     * Fetches the API key information for the given user
     *
     * @param int $id The ID of the key to fetch
     * @return mixed A stdClass object representing the API key, false if no such key exists
     */
    public function get($id)
    {
        $this->Record = $this->keys();

        return $this->Record->where('api_keys.id', '=', $id)->fetch();
    }

    /**
     * Builds a partial query to fetch a list of API keys
     *
     * @return Record
     */
    private function keys()
    {
        return $this->Record->select(['api_keys.*', 'companies.name' => 'company_name'])
            ->from('api_keys')
            ->innerJoin('companies', 'companies.id', '=', 'api_keys.company_id', false);
    }

    /**
     * Adds a new API key for the given company ID and user.
     *
     * @param array $vars An array of API credential information including:
     *
     *  - company_id The ID of the company to add the API key for
     *  - user The user to use as the API user
     */
    public function add(array $vars)
    {
        $vars['date_created'] = date('Y-m-d H:i:s');
        $vars['key'] = '';
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'user', 'key', 'date_created', 'notes'];
            $this->Record->insert('api_keys', $vars, $fields);
        }
    }

    /**
     * Updates an API key
     *
     * @param int $id The ID of the API key to edit
     * @param array $vars An array of API key data to update including:
     *
     *  - notes Notes about this key
     *  - user The username of the API key
     *  - company_id The ID of the company the API key belongs to
     */
    public function edit($id, array $vars)
    {
        $vars['id'] = $id;
        $rules = $this->getRules($vars);
        unset($rules['key']);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['user', 'company_id', 'notes'];
            $this->Record->where('id', '=', $id)
                ->update('api_keys', $vars, $fields);
        }
    }

    /**
     * Permanently removes an API key
     *
     * @param int $id The ID of the API key to delete
     */
    public function delete($id)
    {
        $this->Record->from('api_keys')
            ->where('id', '=', $id)
            ->delete();
    }

    /**
     * Generates an API key using the company ID as a seed. Not intended to be
     * invoked independently. See ApiKeys::add().
     *
     * @param string $key The variable to set the key into
     * @param int $company_id The ID of the company to generate the key for
     * @return string The generated key for the given company ID
     * @see ApiKeys::add()
     */
    public function generateKey($key, $company_id)
    {
        // Generate a sufficiently large random value
        $random = new Random();
        $length = 16;
        $data = md5($random::string($length) . uniqid(php_uname('n'), true))
            . md5(uniqid(php_uname('n'), true) . $random::string($length));

        return $this->systemHash($key . $data, $company_id, 'md5');
    }

    /**
     * Validates the given user is unique across all API keys for the given company
     *
     * @param string $user The user to be validated against the given company
     * @param int $company_id The company ID to validate uniqueness across
     * @param int $api_id The ID of the API key (if given) to exclude from the uniqueness test
     * @return bool True if the user is unique for the given company (besides this $api_id), false otherwise
     */
    public function validateUniqueUser($user, $company_id, $api_id = null)
    {
        $this->Record->select('id')->from('api_keys')
            ->where('user', '=', $user)
            ->where('company_id', '=', $company_id);

        if ($api_id !== null) {
            $this->Record->where('id', '!=', $api_id);
        }

        return !($this->Record->numResults() > 0);
    }

    /**
     * Rules to validate when adding an API key
     *
     * @param array $vars An array of input fields to validate against
     * @return array Rules to validate
     */
    private function getRules(array $vars)
    {
        $rules = [
            'company_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('ApiKeys.!error.company_id.exists')
                ]
            ],
            'user' => [
                'format' => [
                    'rule' => ['betweenLength', 3, 64],
                    'message' => $this->_('ApiKeys.!error.user.format')
                ],
                'unique' => [
                    'rule' => [
                        [$this, 'validateUniqueUser'],
                        (isset($vars['company_id']) ? $vars['company_id'] : null),
                        (isset($vars['id']) ? $vars['id'] : null)
                    ],
                    'message' => $this->_('ApiKeys.!error.user.unique')
                ]
            ],
            'key' => [
                'generate' => [
                    'pre_format' => [[$this, 'generateKey'], $vars['company_id']],
                    'rule' => ['betweenLength', 16, 64],
                    'message' => $this->_('ApiKeys.!error.key.generate')
                ]
            ]
        ];

        return $rules;
    }
}
