<?php
namespace Blesta\Core\Util\DataFeed\Feeds;

use Blesta\Core\Util\DataFeed\Common\AbstractDataFeed;
use Blesta\Core\Util\Input\Fields\InputFields;
use Configure;
use Language;
use Loader;

/**
 * Client feed
 *
 * @package blesta
 * @subpackage blesta.core.Util.DataFeed.Feeds
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientFeed extends AbstractDataFeed
{
    /**
     * @var array An array of options
     */
    private $options = [];

    /**
     * Initialize client feed
     */
    public function __construct()
    {
        parent::__construct();

        // Autoload the language file
        Language::loadLang(
            'client_feed',
            $this->options['language'] ?? Configure::get('Blesta.language'),
            COREDIR . 'Util' . DS . 'DataFeed' . DS . 'Feeds' . DS . 'language' . DS
        );
    }

    /**
     * Returns the name of the data feed
     *
     * @return string The name of the data feed
     */
    public function getName()
    {
        return Language::_('ClientFeed.name', true);
    }

    /**
     * Returns the description of the data feed
     *
     * @return string The description of the data feed
     */
    public function getDescription()
    {
        return Language::_('ClientFeed.description', true);
    }

    /**
     * Executes and returns the result of a given endpoint
     *
     * @param string $endpoint The endpoint to execute
     * @param array $vars An array containing the feed parameters
     * @return mixed The data feed response
     */
    public function get($endpoint, array $vars = [])
    {
        switch ($endpoint) {
            case 'count':
                return $this->countEndpoint($vars);
            default:
                return Language::_('ClientFeed.!error.invalid_endpoint', true);
        }
    }

    /**
     * Sets options for the data feed
     *
     * @param array $options An array of options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Gets a list of the options input fields
     *
     * @param array $vars An array containing the posted fields
     * @return InputFields An object representing the list of input fields
     */
    public function getOptionFields(array $vars = [])
    {
        $fields = new InputFields();

        $base_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
            . '://' . Configure::get('Blesta.company')->hostname . WEBDIR;
        $fields->setHtml('
            <div class="title_row"><h3>' . Language::_('ClientFeed.getOptionFields.title_row_example_code', true) . '</h3></div>
            <div class="pad">
                <small>' . Language::_('ClientFeed.getOptionFields.example_code_active', true) . '</small>
                <pre class="rounded bg-light text-secondary border border-secondary p-2 m-0 my-1">&lt;script src="' . $base_url . 'feed/client/count/"&gt;&lt;/script&gt;</pre>
                
                <small>' . Language::_('ClientFeed.getOptionFields.example_code_inactive', true) . '</small>
                <pre class="rounded bg-light text-secondary border border-secondary p-2 m-0 my-1">&lt;script src="' . $base_url . 'feed/client/count/?status=inactive"&gt;&lt;/script&gt;</pre>
                
                <small>' . Language::_('ClientFeed.getOptionFields.example_code_fraud', true) . '</small>
                <pre class="rounded bg-light text-secondary border border-secondary p-2 m-0 my-1">&lt;script src="' . $base_url . 'feed/client/count/?status=fraud"&gt;&lt;/script&gt;</pre>
            </div>
        ');

        return $fields;
    }

    /**
     * Gets the number of clients of a particular status
     *
     * @param array $vars An array containing the following items:
     *
     *  - status The status type of the clients to fetch
     *   ('active', 'inactive', 'fraud', default null for all)
     */
    private function countEndpoint(array $vars)
    {
        Loader::loadModels($this, ['Clients']);

        if (!isset($vars['status'])) {
            $vars['status'] = null;
        }

        // Get clients count
        $clients = $this->Clients->getListCount($vars['status']);
        if (($errors = $this->Clients->errors())) {
            $this->setErrors($errors);

            return;
        }

        return $clients ?? 0;
    }
}