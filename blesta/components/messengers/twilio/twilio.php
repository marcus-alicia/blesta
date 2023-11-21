<?php

use Blesta\Core\Util\Input\Fields\InputFields;
use Twilio\Rest\Client;

/**
 * Twilio Messenger
 *
 * @package blesta
 * @subpackage blesta.components.messengers.twilio
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Twilio extends Messenger
{
    /**
     * Initializes the messenger.
     */
    public function __construct()
    {
        // Load configuration required by this messenger
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load the helpers required by this messenger
        Loader::loadHelpers($this, ['Html']);

        // Load the language required by this messenger
        Language::loadLang('twilio', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns all fields used when setting up a messenger, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param array $vars An array of post data submitted to the manage messenger page
     * @return InputFields An InputFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getConfigurationFields(&$vars = [])
    {
        $fields = new InputFields();

        // Phone Number
        $sid = $fields->label(Language::_('Twilio.configuration_fields.phone_number', true), 'twilio_phone_number');
        $fields->setField(
            $sid->attach(
                $fields->fieldText('phone_number', (isset($vars['phone_number']) ? $vars['phone_number'] : null), ['id' => 'twilio_phone_number'])
            )
        );

        // SID
        $sid = $fields->label(Language::_('Twilio.configuration_fields.sid', true), 'twilio_sid');
        $fields->setField(
            $sid->attach(
                $fields->fieldText('sid', (isset($vars['sid']) ? $vars['sid'] : null), ['id' => 'twilio_sid'])
            )
        );

        // Token
        $token = $fields->label(Language::_('Twilio.configuration_fields.token', true), 'twilio_token');
        $fields->setField(
            $token->attach(
                $fields->fieldText('token', (isset($vars['token']) ? $vars['token'] : null), ['id' => 'twilio_token'])
            )
        );

        return $fields;
    }

    /**
     * Updates the meta data for this messenger
     *
     * @param array $vars An array of messenger info to add
     * @return array A numerically indexed array of meta fields containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function setMeta(array $vars)
    {
        $meta_fields = ['phone_number', 'sid', 'token'];
        $encrypted_fields = ['token'];

        $meta = [];
        foreach ($vars as $key => $value) {
            if (in_array($key, $meta_fields)) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Send a message.
     *
     * @param mixed $to_user_id The user ID this message is to
     * @param string $content The content of the message to send
     * @param string $type The type of the message to send (optional)
     */
    public function send($to_user_id, $content, $type = null)
    {
        // Initialize the API
        $meta = $this->getMessengerMeta();

        if (!($api = $this->getApi())) {
            return null;
        }

        Loader::loadModels($this, ['Staff', 'Clients', 'Contacts']);

        // Fetch user information
        $is_client = true;
        if (($user = $this->Staff->getByUserId($to_user_id))) {
            $is_client = false;
        } else {
            $user = $this->Clients->getByUserId($to_user_id);

            $phone_numbers = $this->Contacts->getNumbers($user->contact_id);
            if (is_array($phone_numbers) && !empty($phone_numbers)) {
                $user->phone_number = reset($phone_numbers);
            }
        }

        // Send message
        $error = null;
        $success = false;

        if ($type == 'sms') {
            // SMS allows up to 918 characters, by concatenating 6 messages of 153 characters each
            if (strlen($content) > 918) {
                $content = substr($content, 0, 918);
            }

            $params = [
                'from' => $meta->phone_number,
                'body' => $content
            ];
            $this->log($to_user_id, json_encode($params, JSON_PRETTY_PRINT), 'input', true);

            // Send SMS
            try {
                $response = $api->messages->create(
                    (
                        $is_client
                            ? (isset($user->phone_number->number) ? $user->phone_number->number : null)
                            : (isset($user->number_mobile) ? $user->number_mobile : null)
                    ),
                    $params
                );

                $success = empty($response->errorCode);

                $this->log($to_user_id, json_encode($response, JSON_PRETTY_PRINT), 'output', $success);
            } catch (\Twilio\Exceptions\TwilioException $e) {
                $error = $e->getMessage();
                $success = false;

                $this->log($to_user_id, json_encode($error, JSON_PRETTY_PRINT), 'output', $success);
            }
        }
    }

    /**
     * Gets an instance of the Twilio API.
     *
     * @return \Twilio\Rest\Client An instance of the Twilio API or null if the API can't be initialized
     */
    private function getApi()
    {
        $meta = $this->getMessengerMeta();

        try {
            return new Client($meta->sid, $meta->token);
        } catch (\Twilio\Exceptions\ConfigurationException $e) {
            $this->setMessage('error', $e->getMessage());

            return null;
        }
    }
}
