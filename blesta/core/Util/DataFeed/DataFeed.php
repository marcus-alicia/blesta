<?php
namespace Blesta\Core\Util\DataFeed;

use Configure;
use Loader;
use stdClass;

/**
 * Data Feed Utility
 *
 * @package blesta
 * @subpackage blesta.core.Util.DataFeed
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DataFeed
{
    /**
     * Retrieve an instance of the data feed
     *
     * @param string $feed The class name of the data feed to instantiate
     * @param int $company_id The ID of the company where the data feed will be executed
     * @param string $dir The plugin directory where the data feed class is located
     * @return mixed The response of the data feed
     */
    public static function get($feed, $company_id = null, $dir = null)
    {
        // Get the options for the data feed
        $options = self::getOptions($feed, $company_id);

        // Initialize data feed factory
        $factory = new DataFeedFactory();

        return $factory->build($feed, $options, $dir);
    }

    /**
     * Executes a data feed endpoint
     *
     * @param string $feed The class name of the data feed to instantiate
     * @param string $endpoint The endpoint to execute through the data feed
     * @param array $vars An array containing the parameters for the request
     * @param int $company_id The ID of the company where the data feed will be executed
     * @param string $dir The plugin directory where the data feed class is located
     * @return mixed The response of the data feed
     */
    public static function execute($feed, $endpoint, array $vars = [], $company_id = null, $dir = null)
    {
        // Get the options for the data feed
        $options = self::getOptions($feed, $company_id);

        // Initialize data feed factory
        $factory = new DataFeedFactory();
        $instance = $factory->build($feed, $options, $dir);

        $response = $instance->get($endpoint, $vars);
        if (($errors = $instance->errors())) {
            $error = reset($errors);

            return reset($error);
        }

        return $response;
    }

    /**
     * Fetches the default options for the data feed
     *
     * @param string $feed The class name of the data feed to instantiate
     * @param int $company_id The ID of the company where the options will be fetched
     * @param string $dir The plugin directory where the data feed class is located
     * @return array A list containing the data feed options for the current company
     */
    public static function getOptions($feed, $company_id = null, $dir = null)
    {
        $parent = new stdClass();

        Loader::loadModels($parent, ['Companies']);
        Loader::loadComponents($parent, ['SettingsCollection']);

        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        // Get company settings
        $company_settings = $parent->SettingsCollection->fetchSettings(
            $parent->Companies,
            $company_id
        );

        $options = [];

        // Fetch data feed option fields
        $factory = new DataFeedFactory();
        if (($instance = $factory->build($feed, [], $dir))) {
            $fields = $instance->getOptionFields();

            if (!empty($fields)) {
                $requested_fields = [];
                foreach ($fields->getFields() as $field) {
                    if (!empty($field->fields) && $field->type == 'label') {
                        foreach ($field->fields as $sub_field) {
                            $requested_fields[] = $sub_field->params['name'];
                        }
                    } else {
                        $requested_fields[] = $field->params['name'];
                    }
                }

                $options = array_filter($company_settings, function ($key) use ($requested_fields) {
                    return in_array($key, $requested_fields);
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        // Set company id
        $options['company_id'] = $company_id;

        // Set company language
        $options['language'] = $company_settings['language'] ?? Configure::get('Blesta.language');

        return $options;
    }
}