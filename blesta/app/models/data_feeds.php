<?php

use Blesta\Core\Util\DataFeed\Common\AbstractDataFeed;
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;
use Blesta\Core\Util\DataFeed\DataFeed;

/**
 * Data feeds management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DataFeeds extends AppModel
{
    /**
     * Initialize DataFeeds
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['data_feeds']);
    }

    /**
     * Fetches a single data feed including all of its endpoints
     *
     * @param string $feed The class name of the data feed to instantiate
     * @param int $company_id The ID of the company whose data feed is to be fetched
     * @return stdClass An object representing the data feed
     */
    public function get($feed, $company_id = null)
    {
        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $data_feed = $this->Record->select()
            ->from('data_feeds')
            ->where('feed', '=', $feed)
            ->fetch();
        $data_feed->endpoints = $this->Record->select()
            ->from('data_feed_endpoints')
            ->where('feed', '=', $data_feed->feed)
            ->where('company_id', '=', $company_id)
            ->fetchAll();

        // Get data feed name and description
        $instance = DataFeed::get($data_feed->class, $company_id, $data_feed->dir);
        $data_feed->name = $instance->getName();
        $data_feed->description = $instance->getDescription();

        // Set fields
        $fields = $instance->getOptionFields();
        if (!empty($fields)) {
            $data_feed->fields = new FieldsHtml($fields);
        }

        return $data_feed;
    }

    /**
     * Fetches a data feed instance
     *
     * @param string $feed The class name of the data feed to instantiate
     * @return stdClass An object representing the data feed
     */
    public function getInstance($feed)
    {
        $data_feed = $this->Record->select()
            ->from('data_feeds')
            ->where('feed', '=', $feed)
            ->fetch();

        // Get data feed name and description
        return DataFeed::get($data_feed->class, null, $data_feed->dir);
    }

    /**
     * Gets all data feeds with their respective endpoints that meet the given criteria
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - company_id The ID of the company on which to filter the data feeds (optional, default null to fetch all)
     *  - enabled The status of the data feed endpoints (optional, default null) one of the following:
     *      - 1 Fetch only enabled endpoints
     *      - 0 Fetch only disabled endpoints
     *      - null Fetch all endpoints
     * @return array A numerically indexed array of objects, each one representing a data feed
     */
    public function getAll(array $filters = [])
    {
        $data_feeds = $this->Record->select()
            ->from('data_feeds')
            ->fetchAll();

        $feeds = [];
        foreach ($data_feeds as $data_feed) {
            $feed = $this->get($data_feed->feed, ($filters['company_id'] ?? null));

            $this->Record->select()
                ->from('data_feed_endpoints')
                ->where('feed', '=', $feed->feed);

            // Filter on company id
            if (isset($filters['company_id'])) {
                $this->Record->where('company_id', '=', (int) $filters['company_id']);
            }

            // Filter on enabled status
            if (isset($filters['enabled']) && !is_null($filters['enabled'])) {
                $this->Record->where('enabled', '=', (int) $filters['enabled']);
            }

            $feed->endpoints = $this->Record->fetchAll();
            if (!(isset($filters['company_id']) && !isset($filters['enabled']) && empty($feed->endpoints))) {
                $feeds[] = $feed;
            }
        }

        return $feeds;
    }

    /**
     * Executes the endpoint of a data feed for a given company
     *
     * @param string $feed The class name of the data feed to instantiate
     * @param string $endpoint The endpoint to execute through the data feed
     * @param array $vars An array containing the parameters for the request
     * @param int $company_id The ID of the company where the data feed will be executed
     * @return mixed The response of the data feed
     */
    public function execute($feed, $endpoint, array $vars, $company_id = null)
    {
        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $feed_endpoint = $this->Record->select()
            ->from('data_feed_endpoints')
            ->where('feed', '=', $feed)
            ->where('endpoint', '=', $endpoint)
            ->fetch();

        if (empty($feed_endpoint)) {
            return $this->_('DataFeeds.execute.endpoint_not_found');
        }

        if (isset($feed_endpoint->enabled) && $feed_endpoint->enabled == '1') {
            $data_feed = $this->get($feed);

            return DataFeed::execute($data_feed->class, $feed_endpoint->endpoint, $vars, $company_id);
        }
    }

    /**
     * Adds a new data feed
     *
     * @param array $vars An array containing the following parameters:
     *
     *  - feed The name of the data feed
     *  - dir The plugin directory where the data feed belongs (optional, default null)
     *  - class The data feed class
     *  - endpoints
     *      - company_id The ID of the company where the data feed will be added
     *      - endpoint The name of the endpoint
     *      - enabled Whether the endpoint it's enabled
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['feed', 'dir', 'class'];
            $this->Record->insert('data_feeds', $vars, $fields);

            // Add endpoints
            if (!empty($vars['endpoints']) && is_array($vars['endpoints'])) {
                foreach ($vars['endpoints'] as $endpoint) {
                    if (!isset($endpoint['company_id'])) {
                        $endpoint['company_id'] = Configure::get('Blesta.company_id');
                    }
                    $endpoint['feed'] = $vars['feed'];

                    $this->addEndpoint($endpoint);
                }
            }
        }
    }

    /**
     * Edits an existing data feed
     *
     * @param string $feed The feed key/name to be updated
     * @param array $vars A key/value array containing:
     *
     *  - dir The plugin directory where the data feed belongs (optional, default null)
     *  - class The data feed class
     *  - endpoints A multi-dimensional array containing the endpoints to update, each one containing:
     *      - id The ID of the endpoint to update
     *      - company_id The ID of the company where the data feed will be added
     *      - endpoint The name of the endpoint
     *      - enabled Whether the endpoint it's enabled
     */
    public function edit($feed, array $vars)
    {
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['dir', 'class'];
            $this->Record->where('feed', '=', $feed)->update('data_feeds', $vars, $fields);

            if (
                isset($vars['endpoints'])
                && is_array($vars['endpoints'])
                && array_values($vars['endpoints']) == $vars['endpoints']
            ) {
                $feed_endpoints = [];
                foreach ($vars['endpoints'] as $endpoint) {
                    $endpoint['feed'] = $feed;

                    // Update endpoint
                    if (isset($endpoint['id'])) {
                        $feed_endpoints[] = $this->editEndpoint($endpoint['id'], $endpoint);
                    }

                    // Add new endpoint
                    if (empty($endpoint['id'])) {
                        $feed_endpoints[] = $this->addEndpoint($endpoint);
                    }
                }

                // Remove excluded endpoints
                $this->Record->from('data_feed_endpoints')
                    ->where('id', 'not in', $feed_endpoints)
                    ->delete();
            }
        }
    }

    /**
     * Deletes a data feed and all of its endpoints
     *
     * @param string $feed The feed key/name to be deleted
     */
    public function delete($feed)
    {
        $this->Record->from('data_feeds')
            ->where('feed', '=', $feed)
            ->delete();

        $this->Record->from('data_feed_endpoints')
            ->where('feed', '=', $feed)
            ->delete();
    }

    /**
     * Adds a new endpoint to an existing data feed
     *
     * @param array $vars A key/value array containing:
     *
     *  - company_id The ID of the company where the endpoint will be created
     *  - feed The name of the data feed it will belong to
     *  - endpoint The name of the endpoint
     *  - enabled Whether the endpoint it's enabled
     */
    public function addEndpoint(array $vars)
    {
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        $this->Input->setRules($this->getEndpointRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'feed', 'endpoint', 'enabled'];
            $this->Record->insert('data_feed_endpoints', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Fetches an endpoint from the system
     *
     * @param int $endpoint_id The ID of the endpoint to fetch
     * @return stdClass An object representing the endpoint
     */
    public function getEndpoint($endpoint_id)
    {
        return $this->Record->select()
            ->from('data_feed_endpoints')
            ->where('id', '=', $endpoint_id)
            ->fetch();
    }

    /**
     * Gets all endpoints that meet the given criteria
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - company_id The ID of the company on which to filter the endpoints (optional, default null to fetch all)
     *  - enabled The status of the endpoints (optional, default null) one of the following:
     *      - 1 Fetch only enabled endpoints
     *      - 0 Fetch only disabled endpoints
     *      - null Fetch all endpoints
     * @return array A numerically indexed array of objects, each one representing an endpoint
     */
    public function getAllEndpoints(array $filters = [])
    {
        $this->Record->select()
            ->from('data_feed_endpoints');

        // Filter on company id
        if (isset($filters['company_id'])) {
            $this->Record->where('company_id', '=', (int) $filters['company_id']);
        }

        // Filter on enabled status
        if (isset($filters['enabled']) && !is_null($filters['enabled'])) {
            $this->Record->where('enabled', '=', (int) $filters['enabled']);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Updates an existing endpoint
     *
     * @param int $endpoint_id The ID of the endpoint to be updated
     * @param array $vars A key/value array containing:
     *
     *  - feed The name of the data feed it will belong to
     *  - endpoint The name of the endpoint
     *  - enabled Whether the endpoint it's enabled
     * @return int The ID of the endpoint
     */
    public function editEndpoint($endpoint_id, array $vars)
    {
        $this->Input->setRules($this->getEndpointRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['feed', 'endpoint', 'enabled'];
            $this->Record->where('id', '=', $endpoint_id)->update('data_feed_endpoints', $vars, $fields);
        }

        return $endpoint_id;
    }

    /**
     * Deletes an endpoint
     *
     * @param int $endpoint_id The ID of the endpoint to be deleted
     */
    public function deleteEndpoint($endpoint_id)
    {
        $this->Record->from('data_feed_endpoints')
            ->where('id', '=', $endpoint_id)
            ->delete();
    }

    /**
     * Returns the rule set for adding/editing data feeds
     *
     * @param array $vars The input vars
     * @return array The data feed rules
     */
    private function getRules($vars, $edit = false)
    {
        $rules = [
            'feed' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('DataFeeds.!error.feed.valid')
                ]
            ],
            'dir' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('DataFeeds.!error.dir.empty')
                ]
            ],
            'class' => [
                'valid' => [
                    'rule' => function ($class) use ($vars) {
                        if (!empty($vars['dir'])) {
                            $file_name = Loader::fromCamelCase(trim($vars['class'], '\\'));
                            Loader::load(PLUGINDIR . $vars['dir'] . DS . 'lib' . DS . $file_name . '.php');
                        }

                        return class_exists($class) && (new $class()) instanceof AbstractDataFeed;
                    },
                    'message' => $this->_('DataFeeds.!error.class.valid')
                ]
            ]
        ];

        if ($edit) {
            $rules['class']['valid']['if_set'] = true;

            unset($rules['feed']);
        }

        return $rules;
    }

    /**
     * Returns the rule set for adding/editing endpoints
     *
     * @param array $vars The input vars
     * @return array The data feed rules
     */
    private function getEndpointRules($vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('DataFeeds.!error.company_id.exists')
                ]
            ],
            'feed' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'feed', 'data_feeds'],
                    'message' => $this->_('DataFeeds.!error.feed.valid')
                ]
            ],
            'endpoint' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('DataFeeds.!error.endpoint.valid')
                ]
            ],
            'enabled' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('DataFeeds.!error.enabled.valid')
                ]
            ]
        ];

        if ($edit) {
            $rules['feed']['valid']['if_set'] = true;
            $rules['endpoint']['valid']['if_set'] = true;

            unset($rules['company_id']);
        }

        return $rules;
    }
}
