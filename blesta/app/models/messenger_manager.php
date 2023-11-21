<?php

use Blesta\Core\Util\Common\Traits\Container;

/**
 * Messenger manager.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MessengerManager extends AppModel
{
    // Load traits
    use Container;

    /**
     * An array of key/value pairs to be used as default tags for message templates
     */
    private $default_tags = [];

    /**
     * Initialize MessengerManager
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['messenger_manager']);
        $company = Configure::get('Blesta.company');

        if ($company) {
            $webdir = WEBDIR;

            // Set default webdir if running via CLI
            if (empty($_SERVER['REQUEST_URI'])) {
                Loader::loadModels($this, ['Settings']);
                $root_web = $this->Settings->getSetting('root_web_dir');
                if ($root_web) {
                    $webdir = str_replace(DS, '/', str_replace(rtrim($root_web->value, DS), '', ROOTWEBDIR));

                    if (!HTACCESS) {
                        $webdir .= 'index.php/';
                    }
                }
            }

            // Set the URIs to the admin/client portals
            $this->default_tags['base_uri'] = $company->hostname . $webdir;
            $this->default_tags['admin_uri'] = $company->hostname . $webdir . Configure::get('Route.admin') . '/';
            $this->default_tags['client_uri'] = $company->hostname . $webdir . Configure::get('Route.client') . '/';
        }
    }

    /**
     * Fetches a single installed messenger including meta data
     *
     * @param int $messenger_id The ID of the messenger to fetch
     * @return mixed A stdClass object representing the installed messenger, false if no such messenger exists
     */
    public function get($messenger_id)
    {
        $fields = ['id', 'dir', 'company_id', 'name', 'version'];
        $messenger = $this->Record->select($fields)
            ->from('messengers')
            ->where('id', '=', $messenger_id)
            ->fetch();

        if ($messenger) {
            // Fetch all messenger meta data
            $messenger->meta = $this->getMeta($messenger_id);
        }

        return $messenger;
    }

    /**
     * Fetches a messenger for a given company, or all messengers installed in the system for the given messenger dir
     *
     * @param string $dir The dir name (in file_case)
     * @param int $company_id The ID of the company to fetch messengers for (optional, default null for all)
     * @return array An array of stdClass objects, each representing an installed messenger record
     */
    public function getByDir($dir, $company_id = null)
    {
        $this->Record->select(['messengers.*'])
            ->from('messengers')
            ->where('dir', '=', $dir);

        if ($company_id !== null) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Lists all installed messengers
     *
     * @param int $company_id The company ID
     * @param string $sort_by The field to sort by
     * @param string $order The direction to order results
     * @return array An array of stdClass objects representing installed messengers
     */
    public function getAll($company_id, $sort_by = 'name', $order = 'asc')
    {
        $fields = ['id', 'dir', 'company_id', 'name', 'version'];
        $messengers = $this->Record->select($fields)
            ->from('messengers')
            ->where('company_id', '=', $company_id)
            ->order([$sort_by => $order])
            ->fetchAll();

        // Load each installed messenger to fetch messenger info
        foreach ($messengers as $i => $messenger) {
            try {
                $instance = $this->loadMessenger($messenger->dir);

                // Set the installed version of the messenger
                $messengers[$i]->installed_version = $messenger->version;
            } catch (Exception $e) {
                // Messenger could not be loaded
                unset($messengers[$i]);
                continue;
            }

            $info = $this->getMessengerInfo($instance, $company_id);
            foreach ((array) $info as $key => $value) {
                $messengers[$i]->$key = $value;
            }
        }

        return $messengers;
    }

    /**
     * Retrieves a list of messenger meta data for a given messenger ID
     *
     * @param int $messenger_id The messenger ID
     * @param string $key The messenger meta key representing a specific meta value (optional)
     * @return stdClass An object representing all messenger meta info
     */
    public function getMeta($messenger_id, $key = null)
    {
        $fields = ['key', 'value', 'serialized', 'encrypted'];

        $this->Record->select($fields)->from('messenger_meta')->
            where('messenger_id', '=', $messenger_id);

        if ($key != null) {
            $this->Record->where('key', '=', $key);
        }

        return $this->formatRawMeta($this->Record->fetchAll());
    }

    /**
     * Checks whether the given messenger is installed for the specified company
     *
     * @param string $dir The messenger directory name (in file_case)
     * @param string $company_id The ID of the company to fetch for (null
     *  checks if the messenger is installed across any company)
     * @return bool True if the messenger is installed, false otherwise
     */
    public function isInstalled($dir, $company_id = null)
    {
        $this->Record->select(['messengers.id'])->from('messengers')->
            where('dir', '=', $dir);

        if ($company_id) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return (boolean) $this->Record->fetch();
    }

    /**
     * Runs the messenger's upgrade method to upgrade the messenger to match that of the messenger's file version.
     * Sets errors in MessengerManager::errors() if any errors are set by the messenger's upgrade method.
     *
     * @param int $messenger_id The ID of the messenger to upgrade
     * @see MessengerManager::errors()
     */
    public function upgrade($messenger_id)
    {
        $installed_messenger = $this->get($messenger_id);

        if (!$installed_messenger) {
            return;
        }

        $messenger = $this->loadMessenger($installed_messenger->dir);
        $messenger->setMessenger($installed_messenger);

        $file_version = $messenger->getVersion();

        // Execute the upgrade if the installed version doesn't match the file version
        if (version_compare($file_version, $installed_messenger->version, '!=')) {
            $messenger->upgrade($installed_messenger->version);

            if (($errors = $messenger->errors())) {
                $this->Input->setErrors($errors);
            } else {
                // Update all installed messenger to the given version
                $this->setVersion($installed_messenger->dir, $file_version);
            }
        }
    }

    /**
     * Updates all installed messengers with the version given
     *
     * @param string $dir The dir name of the messenger to update
     * @param string $version The version number to set for each module instance
     */
    private function setVersion($dir, $version)
    {
        $this->Record->where('dir', '=', $dir)->update('messengers', ['version' => $version]);
    }

    /**
     * Adds the messenger to the system, executing the Messenger::install() method
     *
     * @param array $vars An array of messenger data including:
     *
     *  - company_id The company ID
     *  - dir The messenger directory name
     * @return int The ID of the messenger installed, void on error
     */
    public function add(array $vars)
    {
        // Retrieve the messenger
        $messenger = $this->loadMessenger(isset($vars['dir']) ? $vars['dir'] : null);

        $vars['name'] = $messenger->getName();
        $vars['version'] = $messenger->getVersion();

        // Attempt to install the messenger
        $meta = $messenger->install();

        // If the installation failed for some reason, return nothing
        // we can do
        if (($errors = $messenger->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        $rules = [
            'company_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('MessengerManager.!error.company_id.valid')
                ]
            ],
            'dir' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('MessengerManager.!error.dir.valid')
                ]
            ],
            'name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('MessengerManager.!error.name.valid')
                ]
            ],
            'version' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('MessengerManager.!error.version.valid')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        // Add the messenger to the database
        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'name', 'dir', 'version'];
            $this->Record->insert('messengers', $vars, $fields);

            $messenger_id = $this->Record->lastInsertId();

            // Set any messenger meta from the install
            if (!empty($meta) && is_array($meta)) {
                $this->setMeta($messenger_id, $meta);
            }

            // Trigger the event
            $eventFactory = $this->getFromContainer('util.events');
            $eventListener = $eventFactory->listener();
            $eventListener->register('MessengerManager.add');
            $eventListener->trigger(
                $eventFactory->event('MessengerManager.add', ['messenger_id' => $messenger_id, 'vars' => $vars])
            );

            return $messenger_id;
        }
    }

    /**
     * Permanently and completely removes the messenger from the database,
     * along with all messenger records. Executes the Messenger::uninstall() method
     *
     * @param int $messenger_id The ID of the messenger to permanently and completely remove
     */
    public function delete($messenger_id)
    {
        $installed_messenger = $this->get($messenger_id);

        $this->Record->from('messengers')->where('id', '=', $messenger_id)->delete();
        $this->Record->from('messenger_meta')->where('messenger_id', '=', $messenger_id)->delete();

        if ($installed_messenger) {
            // It's the responsibility of the messenger to remove any other tables or entries
            // it has created that are no longer relevant
            $messenger = $this->loadMessenger($installed_messenger->dir);
            $messenger->setMessenger($installed_messenger);

            $messenger->uninstall($messenger_id, !$this->isInstalled($installed_messenger->dir));

            if (($errors = $messenger->errors())) {
                $this->Input->setErrors($errors);
            }
        }

        // Trigger the event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('MessengerManager.delete');
        $eventListener->trigger(
            $eventFactory->event(
                'MessengerManager.delete',
                ['messenger_id' => $messenger_id, 'old_messenger' => $installed_messenger]
            )
        );
    }

    /**
     * Lists all available messengers (those that exist on the file system)
     *
     * @param int $company_id The ID of the company to get available messengers for
     * @return array An array of stdClass objects representing available messengers
     */
    public function getAvailable($company_id = null)
    {
        $messengers = [];

        $dir = opendir(COMPONENTDIR . 'messengers');
        while (false !== ($messenger = readdir($dir))) {
            // If the file is not a hidden file, and is a directory, accept it
            if (substr($messenger, 0, 1) != '.' && is_dir(COMPONENTDIR . 'messengers' . DS . $messenger)) {
                try {
                    $mod = $this->loadMessenger($messenger);
                } catch (Exception $e) {
                    // The messenger could not be loaded, try the next
                    continue;
                }

                $messengers[] = (object) $this->getMessengerInfo($mod, $company_id);
            }
        }
        return $messengers;
    }

    /**
     * Updates the meta data for the given messenger, removing all existing data and replacing it with the given data
     *
     * @param int $messenger_id The ID of the messenger to update
     * @param array $vars A numerically indexed array of meta data containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function setMeta($messenger_id, array $vars)
    {
        $messenger = $this->initMessenger($messenger_id);

        if ($messenger) {
            // Notify the messenger of the attempt to set the meta data
            $meta = $messenger->setMeta($vars);

            // Delete all old meta data for this messenger
            $this->Record->from('messenger_meta')
                ->where('messenger_id', '=', $messenger_id)
                ->delete();

            // Add all new messenger data
            $fields = ['messenger_id', 'key', 'value', 'serialized', 'encrypted'];
            $num_meta = count($meta);
            for ($i = 0; $i < $num_meta; $i++) {
                $serialize = !is_scalar($meta[$i]['value']);
                $meta[$i]['messenger_id'] = $messenger_id;
                $meta[$i]['serialized'] = (int) $serialize;
                $meta[$i]['value'] = $serialize ? serialize($meta[$i]['value']) : $meta[$i]['value'];

                if (isset($meta[$i]['encrypted']) && $meta[$i]['encrypted'] == '1') {
                    $meta[$i]['value'] = $this->systemEncrypt($meta[$i]['value']);
                }

                $this->Record->insert('messenger_meta', $meta[$i], $fields);
            }
        }
    }

    /**
     * Formats an array of raw meta stdClass objects into a stdClass
     * object whose public member variables represent meta keys and whose values
     * are automatically decrypted and unserialized as necessary.
     *
     * @param array $raw_meta An array of stdClass objects representing meta data
     * @return stdClass An object containing the formatted meta data
     */
    private function formatRawMeta($raw_meta)
    {
        $meta = new stdClass();

        // Decrypt data as necessary
        foreach ($raw_meta as &$data) {
            if ($data->encrypted > 0) {
                $data->value = $this->systemDecrypt($data->value);
            }

            if ($data->serialized > 0) {
                $data->value = unserialize($data->value);
            }

            $meta->{$data->key} = $data->value;
        }

        return $meta;
    }

    /**
     * Fetch information about the given messenger object
     *
     * @param Messenger $messenger The messenger object to fetch info on
     * @param int $company_id The ID of the company to fetch the messenger info for
     * @return array key=>value pairs of messenger info
     */
    private function getMessengerInfo($messenger, $company_id)
    {
        // Fetch supported interfaces
        $reflect = new ReflectionClass($messenger);
        $dir = Loader::fromCamelCase($reflect->getName());

        $dirname = dirname($_SERVER['SCRIPT_NAME']);
        $info = [
            'dir' => $dir,
            'name' => $messenger->getName(),
            'version' => $messenger->getVersion(),
            'authors' => $messenger->getAuthors(),
            'types' => $messenger->getTypes(),
            'logo' => Router::makeURI(
                ($dirname == DS ? '' : $dirname) . DS
                . str_replace(
                    ROOTWEBDIR,
                    '',
                    COMPONENTDIR . 'messengers' . DS . $dir . DS . $messenger->getLogo()
                )
            ),
            'installed' => $this->isInstalled($dir, $company_id),
            'description' => $messenger->getDescription()
        ];

        unset($reflect);

        return $info;
    }

    /**
     * Initializes the messenger if it has been installed and returns its instance
     *
     * @param int $messenger_id The ID of the messenger to initialize
     * @param int $company_id If set will check to ensure the messenger belongs to the given company_id
     * @return Messenger An object of type Messenger if the requested messenger has been installed and exists, false otherwise
     */
    public function initMessenger($messenger_id, $company_id = null)
    {
        $installed_messenger = $this->get($messenger_id);

        if ($installed_messenger && ($company_id === null || $company_id == $installed_messenger->company_id)) {
            $messenger = $this->loadMessenger($installed_messenger->dir);
            $messenger->setMessenger($installed_messenger);

            return $messenger;
        }

        return false;
    }

    /**
     * Instantiates the given messenger and returns its instance
     *
     * @param string $dir The name of the directory name in file_case to load
     * @return Messenger An instance of the messenger specified
     */
    private function loadMessenger($dir)
    {
        // Load the messenger factory if not already loaded
        if (!isset($this->Messengers)) {
            Loader::loadComponents($this, ['Messengers']);
        }

        // Instantiate the messenger and return the instance
        return $this->Messengers->create($dir);
    }

    /**
     * Send a message for the given action
     *
     * @param string $action The action for which to send a message
     * @param array $tags A list of tags to include in the message
     * @param array $user_ids A list of IDs for the users that should receive this message
     * @param string $message_type The type of the message to send, it can be 'sms' or null (null by default)
     * @param mixed $deliver_to The destination of the message (null by default)
     */
    public function send($action, array $tags, array $user_ids, $message_type = null, $deliver_to = null)
    {
        if (!isset($this->Logs)) {
            Loader::loadModels($this, ['Logs']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Staff)) {
            Loader::loadModels($this, ['Staff']);
        }
        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }
        if (!isset($this->Messages)) {
            Loader::loadModels($this, ['Messages']);
        }
        if (!isset($this->MessageGroups)) {
            Loader::loadModels($this, ['MessageGroups']);
        }
        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        $company_id = Configure::get('Blesta.company_id');

        $message_group = $this->MessageGroups->getByAction($action);
        $message_group_with_messages = $this->Messages->getByGroup($message_group->id ?? null);

        if (empty($message_group)) {
            // Error, no messenger is set
            $this->Input->setErrors(['messenger' => ['missing' => Language::_('MessengerManager.!error.messenger.missing', true)]]);

            return;
        }

        // Get a list of messengers assigned to each of this message types
        $type_messages = [];
        $enabled_type_messages = $this->Messages->getMessageGroupEnabledTypes($message_group->id);
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $company_id);
        $messenger_configuration = isset($company_settings['messenger_configuration'])
            ? unserialize(base64_decode($company_settings['messenger_configuration']))
            : [];
        foreach ($message_group_with_messages->messages as $message) {
            if (!in_array($message->type, $enabled_type_messages)) {
                continue;
            }

            if (!empty($messenger_configuration[$message->type])) {
                $type_messages[$message->type] = [
                    'messenger_id' => $messenger_configuration[$message->type],
                    'message' => $message
                ];
            } else {
                $this->logger->notice(
                    'Message Not Sent',
                    ['messenger' => ['missing' => Language::_('MessengerManager.!error.messenger.missing', true)]]
                );
            }
        }

        // Use the messenger for each type to send the message to each staff user
        foreach ($type_messages as $type => $message) {
            // Skip this message if type doesn't match with the one provided
            if ($message_type !== $type && !is_null($message_type)) {
                continue;
            }

            // Skip this message if it's inactive
            if (($message['message']->status ?? 'inactive') !== 'active') {
                continue;
            }

            $messenger = $this->initMessenger($message['messenger_id']);
            if (!$messenger) {
                $this->logger->notice(
                    'Message Not Sent',
                    ['messenger' => ['missing' => Language::_('MessengerManager.!error.messenger.missing', true)]]
                );

                continue;
            }

            foreach ($user_ids as $user_id) {
                $language = Configure::get('Blesta.language');
                if (($staff = $this->Staff->getByUserId($user_id))) {
                    $language = $this->Staff->getSetting(
                        $staff->id,
                        'language'
                    )->value ?? Configure::get('Blesta.language');
                } elseif (($client = $this->Clients->getByUserId($user_id, true))) {
                    $language = $client->settings['language'];
                }

                $content = $message['message']->content[($language->value ?? $language)] ??
                    ($message['message']->content[Configure::get('Blesta.language')] ?? '');

                // Perform tag replacement
                $final_text = $this->buildMessage($content->content, $company_id, $tags);

                if (!empty($deliver_to) && method_exists($messenger, 'sendTo')) {
                    $messenger->sendTo($deliver_to, $user_id, $final_text, $type);
                } else {
                    $messenger->send($user_id, $final_text, $type);
                }
            }
        }
    }

    /**
     * Parses message text using the given data ($tags)
     *
     * @param string $text The initial text for a message to be parsed
     * @param int $company_id The company ID to send this message under
     * @param array $tags An array of replacement tags containing the key/value
     *  pairs for the replacements where the key is the tag to replace and the
     *  value is the value to replace it with
     * @return string The parsed message template
     */
    public function buildMessage($text, $company_id, array $tags = null)
    {
        // Merge the default tags with those given
        $tags = array_merge($this->default_tags, (array) $tags);

        // Load the template parser
        $parser = new H2o();
        $this->setFilters($parser, $company_id);

        $parser_options = Configure::get('Blesta.parser_options');
        // Don't escape text
        $parser_options['autoescape'] = false;

        // Parse the tex using template parser
        $text = $parser->parseString($text, $parser_options)->render($tags);

        return $text;
    }

    /**
     * Sets filters on the parser
     *
     * @param object $parser The parser to set filters on
     * @param int $company_id The company ID to set filters from
     */
    private function setFilters($parser, $company_id)
    {
        if (!isset($this->CurrencyFormat)) {
            Loader::loadHelpers($this, ['CurrencyFormat']);
        }
        $this->CurrencyFormat->setCompany($company_id);
        $parser->addFilter('currency_format', [$this->CurrencyFormat, 'format']);
    }
}
