<?php

/**
 * Company Encryption Settings
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Encryption extends AppModel
{
    /**
     * Initialize Encryption settings
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['encryption']);
    }

    /**
     * Sets the new passphrase used to encrypt/decrypt the private key. When a
     * blank private_key_passphrase is used the Blesta.system_key is used to
     * encrypt the private key, allowing the system to decrypt it at will. However,
     * if a private_key_passphrase is set that value is instead used to encrypt
     * the private key and therefore only that key (which Blesta does not store)
     * can be used to decrypt the private key.
     *
     * @param array $vars An array of passphrase info including:
     *  - current_passphrase The current passphrase in use (leave blank if no passphrase currently set)
     *  - private_key_passphrase The new password to use
     *  - confirm_new_passphrase The new password again, to confirm (optional)
     *  - agree Set to some non-empty value (e.g. "yes") if the user
     *      understands the ramifications of setting a passphrase
     * @param bool $require_agree Set to true to require that user has saved the passphrase to a safe location
     */
    public function setPassphrase(array $vars, $require_agree = false)
    {
        $company_id = Configure::get('Blesta.company_id');

        // Get the company settings
        Loader::loadComponents($this, ['SettingsCollection']);
        $company_settings = $this->SettingsCollection->fetchSettings(null, $company_id);

        // Set the private key
        $vars['private_key'] = $company_settings['private_key'];

        // Validate the new passphrase
        $rules = [
            'confirm_new_passphrase' => [
                'matches' => [
                    'if_set' => true,
                    'rule' => [
                        'compares',
                        '==',
                        isset($vars['private_key_passphrase']) ? $vars['private_key_passphrase'] : null
                    ],
                    'message' => $this->_('Encryption.!error.confirm_new_passphrase.matches')
                ]
            ]
        ];

        // If requiring agreement on setting password, ensure agreement was given
        if ($require_agree) {
            $rules['agree'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Encryption.!error.agree.empty')
                ]
            ];
        }

        // Handle decrypting/encrypting the private key
        $rules['private_key'] = [
            'empty' => [
                'rule' => 'isEmpty',
                'negate' => true,
                // Decrypt the old passphrase whether it be with a previous passphrase or the default passphrase
                'pre_format' => [
                    [$this, 'systemDecrypt'],
                    !empty($vars['current_passphrase']) ? $vars['current_passphrase'] : null
                ],
                // Encrypt the private key with the new passphrase or the default passphrase if not set
                'post_format' => [
                    [$this, 'systemEncrypt'],
                    !empty($vars['private_key_passphrase']) ? $vars['private_key_passphrase'] : null
                ]
            ]
        ];

        // If this company has a private key passphrase on record, then it must be given
        if (!empty($company_settings['private_key_passphrase'])) {
            $rules['current_passphrase'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Encryption.!error.current_passphrase.empty')
                ],
                'matches' => [
                    'pre_format' => [[$this, 'systemHash']], // Hash the current passphrase
                    // compare current passphrase with what was entered
                    'rule' => ['compares', '==', $company_settings['private_key_passphrase']],
                    'message' => $this->_('Encryption.!error.current_passphrase.matches')
                ]
            ];
        }

        $this->Input->setRules($rules);
        unset($rules);

        if ($this->Input->validates($vars)) {
            // Store a hash copy of the private key passphrase for comparrison purposes, only if non-empty
            $vars['private_key_passphrase'] = !empty($vars['private_key_passphrase'])
                ? $this->systemHash($vars['private_key_passphrase'])
                : '';

            // Update the passphrase
            $this->Record->duplicate('value', '=', $vars['private_key_passphrase'])
                ->insert(
                    'company_settings',
                    [
                        'key' => 'private_key_passphrase',
                        'company_id' => $company_id,
                        'value' => $vars['private_key_passphrase']
                    ]
                );

            // Update the private key
            $this->Record->where('company_id', '=', $company_id)
                ->where('key', '=', 'private_key')
                ->update('company_settings', ['value' => $vars['private_key']]);

            unset($vars);
        }
    }

    /**
     * Verify if the given passphrase is valid
     *
     * @param string $passphrase The passphrase to test
     * @return bool True if the passphrase is valid, false otherwise
     */
    public function verifyPassphrase($passphrase)
    {
        $company_id = Configure::get('Blesta.company_id');

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Fetch the passphrase value
        $pass = $this->SettingsCollection->fetchSetting(null, $company_id, 'private_key_passphrase');
        $pass = (isset($pass['value']) ? $pass['value'] : null);

        return (($pass == '' && $pass == $passphrase) || $this->systemHash($passphrase) == $pass);
    }
}
