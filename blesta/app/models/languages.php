<?php

/**
 * Language management. Maintains all languages installed on the system and
 * allows new languages to be installed.
 *
 * All language codes are in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (i.e. "en_us")
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Languages extends AppModel
{
    /**
     * Initialize Languages
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['languages']);
    }

    /**
     * Fetches an installed language available to the given company
     *
     * @param int $company_id The ID of the company to fetch installed languages under.
     * @param string $code The language installed in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     * @param mixed An stdClass object representing the installed language, or false if it does not exist
     */
    public function get($company_id, $code)
    {
        return $this->Record->select()
            ->from('languages')
            ->where('company_id', '=', $company_id)
            ->where('code', '=', $code)
            ->fetch();
    }

    /**
     * Fetches all languages installed at the given company
     *
     * @param int $company_id The ID of the company to fetch installed languages under.
     * @return array An array of stdClass objects representing languages installed under the given company.
     */
    public function getAll($company_id)
    {
        return $this->Record->select(['code', 'company_id', 'name'])
            ->from('languages')
            ->where('company_id', '=', $company_id)
            ->fetchAll();
    }

    /**
     * Get all available languages on the file system
     *
     * @return array An array of key/value pairs representing languages available on the file system.
     * Where the key is the language code and the value is the name of the language in its native form.
     */
    public function getAvailable()
    {
        $languages = [];

        // Read from the file system to fetch all available languages
        $dir = opendir(LANGDIR);
        while (false !== ($file = readdir($dir))) {
            // If the file is not a hidden file, and is a directory, accept it
            if (substr($file, 0, 1) != '.' && is_dir(LANGDIR . $file)) {
                $languages[$file] = $this->getNameFromFile($file);
            }
        }
        closedir($dir);

        return $languages;
    }

    /**
     * Installs the given language file for the given company, if it exists
     *
     * @param int $company_id The ID of the company under which the language should be installed
     * @param string $code The language to install in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     */
    public function add($company_id, $code)
    {
        $vars = ['company_id' => $company_id, 'code' => $code];

        // Fetch the name of the language
        if (is_dir(LANGDIR . $code)) {
            $vars['name'] = $this->getNameFromFile($code);
        }

        $rules = [
            'code' => [
                'format' => [
                    'rule' => ['matches', '/^[a-z]{2}_[a-z]{2}$/i'],
                    'message' => $this->_('Languages.!error.code.format'),
                    'post_format' => 'strtolower'
                ],
                'unique' => [
                    'rule' => [[$this, 'validateCodeExists'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'negate' => true,
                    'message' => $this->_('Languages.!error.code.unique')
                ]
            ],
            'company_id' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => $this->_('Languages.!error.company_id.format')
                ]
            ],
            'name' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Languages.!error.name.format')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['code', 'company_id', 'name'];
            $this->Record->insert('languages', $vars, $fields);

            // Add email templates for this language
            $fields = ['email_group_id', 'company_id', 'from', 'from_name', 'subject',
                'text', 'html', 'email_signature_id', 'status'
            ];

            $language = $this->getDefaultLang($company_id);

            // Fetch the default language email templates
            $emails = $this->Record->select($fields)->from('emails')->where('lang', '=', $language)->
                where('company_id', '=', $company_id)->fetchAll();

            // Insert new email templates based on default language templates
            $fields[] = 'lang';

            foreach ($emails as $email) {
                $email_vars = (array) $email;
                $email_vars['lang'] = $code;
                $this->Record->insert('emails', $email_vars, $fields);
            }

            // Add message templates for this language
            $fields = ['message_id', 'lang', 'content'];
            $messages = $this->Record->select($fields)
                ->from('message_content')
                ->innerJoin('messages', 'message_content.message_id', '=', 'messages.id', false)
                ->innerJoin('message_groups', 'messages.message_group_id', '=', 'message_groups.id', false)
                ->where('lang', '=', $language)
                ->where('company_id', '=', $company_id)
                ->fetchAll();

            foreach ($messages as $message) {
                $message_vars = (array) $message;
                $message_vars['lang'] = $code;
                $this->Record->insert('message_content', $message_vars, $fields);
            }
        }
    }

    /**
     * Uninstalls the given language
     *
     * @param int $company_id The ID of the company to uninstall the given language from
     * @param string $code The language code under the given company to uninstall
     */
    public function delete($company_id, $code)
    {
        // Can never remove the default language
        $rules = [
            'code' => [
                'valid' => [
                    'rule' => [[$this, 'validateDefaultLang'], $company_id],
                    'negate' => true,
                    'message' => $this->_('Languages.!error.code.valid')
                ]
            ]
        ];
        $this->Input->setRules($rules);
        $vars = ['code' => $code];

        if ($this->Input->validates($vars)) {
            // Remove the language
            $this->Record->from('languages')->where('code', '=', $code)->
                where('company_id', '=', $company_id)->delete();

            // Remove all emails that use this language at this company as well
            $this->Record->from('emails')->where('lang', '=', $code)->
                where('company_id', '=', $company_id)->delete();

            // Remove all messages that use this language at this company as well
            $this->Record->from('message_content')->
                innerJoin('messages', 'message_content.message_id', '=', 'messages.id', false)->
                where('messages.company_id', '=', $company_id)->
                where('message_content.lang', '=', $code)->
                delete(['message_content.*']);

            // Fetch the default language
            $default_language = $this->getDefaultLang($company_id);

            // Update all clients/staff that used the previous language to now use the default language
            $this->Record->
                innerJoin('clients', 'clients.id', '=', 'client_settings.client_id', false)->
                innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                where('client_groups.company_id', '=', $company_id)->
                where('client_settings.key', '=', 'language')->
                where('client_settings.value', '=', $code)->
                set('client_settings.value', $default_language)->
                update('client_settings');

            $this->Record->
                innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff_group.staff_id', false)->
                innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
                where('staff_groups.company_id', '=', $company_id)->
                where('staff_settings.key', '=', 'language')->
                where('staff_settings.value', '=', $code)->
                set('staff_settings.value', $default_language)->
                update('staff_settings');
        }
    }

    /**
     * Retrieves a list of all languages that cannot be uninstalled for this company
     *
     * @param int $company_id The ID of the company whose uninstallable languages to fetch
     * @return array A list of uninstallable languages
     */
    public function getAllUninstallable($company_id)
    {
        // Uninstallable languages
        $uninstallable = [];

        // Get the default language
        $default_lang = $this->getDefaultLang($company_id);

        // en_us may not be uninstalled
        if ($default_lang != 'en_us') {
            $uninstallable[] = 'en_us';
        }
        $uninstallable[] = $default_lang;

        return $uninstallable;
    }

    /**
     * Fetches the name of the language from the file system if the language exists
     * and contains the language name file
     *
     * @param string $code The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     * @return string The name of the language from the language name file
     *  on the file system if it exists, else the $code.
     */
    private function getNameFromFile($code)
    {
        $name = null;

        if (file_exists(LANGDIR . $code . DS . $code)) {
            $name = file_get_contents(LANGDIR . $code . DS . $code);
        }

        // Revert back to the code if the name of the language is not present
        if ($name == '') {
            $name = $code;
        }

        return $name;
    }

    /**
     * Validates whether or not the $code and $company_id combination already exists
     *
     * @param string $code The ISO 639-1 ISO 3166-1 alpha-2 code in concatenated format (e.g. "en_us")
     * @param int $company_id The company ID
     * @return bool True if the $code and $company_id combination already exists, false otherwise
     */
    public function validateCodeExists($code, $company_id)
    {
        $result = $this->Record->select('code')->from('languages')->
            where('code', '=', $code)->where('company_id', '=', $company_id)->
            numResults();

        if ($result > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates whether or not the $code and $company_id combination are the default language, or 'en_us'
     *
     * @param string $code The ISO 639-1 ISO 3166-1 alpha-2 code in concatenated format (e.g. "en_us")
     * @param int $company_id The company ID
     * @return bool True if the $code and $company_id are the default language or 'en_us', false otherwise
     */
    public function validateDefaultLang($code, $company_id)
    {
        if (preg_match('/^(en_us)$/i', $code) || ($this->getDefaultLang($company_id) == strtolower($code))) {
            return true;
        }
        return false;
    }

    /**
     * Fetches the default language code for this company
     *
     * @param int $company_id The ID of the company whose default language to fetch
     * @return string The language code (e.g. "en_us")
     */
    private function getDefaultLang($company_id)
    {
        // Company Settings
        $sql1 = $this->Record->select(['key', 'value'])->from('company_settings')->
            where('key', '=', 'language')->
            where('company_id', '=', $company_id)->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql2 = $this->Record->select(['key', 'value'])->from('settings')->
            where('key', '=', 'language')->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $language = $this->Record->select()->from(['((' . $sql1 . ') UNION (' . $sql2 . '))' => 'temp'])->
            where('temp.key', '=', 'language')->fetch();

        return $language->value;
    }
}
