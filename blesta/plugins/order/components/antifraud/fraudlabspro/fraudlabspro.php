<?php
/**
 * FraudLabs Pro Fraud Detection
 *
 * @package blesta
 * @subpackage blesta.plugins.order.components.antifraud.fraudlabspro
 * @copyright Copyright (c) 2014, Hexasoft Development Sdn Bhd.
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 * @link http://www.fraudlabspro.com/ FraudLabsPro
 */
class FraudLabsPro implements FraudDetect
{
    /**
     * @var array Key/value pair options
     */
    private $options = [];
    /**
     * @var stdClass A stdClass object representing the last API response
     */
    private $result = null;

    public function __construct(array $options)
    {
        Language::loadLang('fraudlabspro', null, dirname(__FILE__) . DS . 'language' . DS);
        Loader::loadComponents($this, ['Input']);
        $this->options = $options;
    }

    /**
     * Returns ModuleFields object containing all settings for the antifraud component
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields
     */
    public function getSettingFields($vars = null)
    {
        $fields = new ModuleFields();

        Loader::loadHelpers($this, ['Html']);

        // API Key
        $api_key = $fields->label(Language::_('FraudLabsPro.settings.field_api_key', true), 'fraudlabspro_api_key');
        $fields->setField(
            $api_key->attach(
                $fields->fieldText(
                    'fraudlabspro_api_key',
                    (isset($vars->fraudlabspro_api_key) ? $vars->fraudlabspro_api_key : null),
                    ['id' => 'fraudlabspro_api_key']
                )
            )
        );

        $fields->setField(
            $fields->label(Language::_('FraudLabsPro.settings.label_description', true))
        );

        // Reject Score
        $reject_score = $fields->label(
            Language::_('FraudLabsPro.settings.field_reject_score', true),
            'fraudlabspro_reject_score'
        );
        $fields->setField(
            $reject_score->attach(
                $fields->fieldText(
                    'fraudlabspro_reject_score',
                    (isset($vars->fraudlabspro_reject_score) ? $vars->fraudlabspro_reject_score : '80'),
                    ['id' => 'fraudlabspro_reject_score']
                )
            )
        );

        // Review Score
        $review_score = $fields->label(
            Language::_('FraudLabsPro.settings.field_review_score', true),
            'fraudlabspro_review_score'
        );
        $fields->setField(
            $review_score->attach(
                $fields->fieldText(
                    'fraudlabspro_review_score',
                    (isset($vars->fraudlabspro_review_score) ? $vars->fraudlabspro_review_score : '10'),
                    ['id' => 'fraudlabspro_review_score']
                )
            )
        );

        // Follow FraudLabs Pro result
        $follow_flp_result = $fields->label(
            Language::_('FraudLabsPro.settings.field_follow_flp_result', true),
            'fraudlabspro_follow_flp_result'
        );
        $follow_flp_result->attach(
            $fields->fieldRadio(
                'fraudlabspro_follow_flp_result',
                'yes',
                (isset($vars->fraudlabspro_follow_flp_result) ? $vars->fraudlabspro_follow_flp_result : 'yes') == 'yes',
                ['id' => 'fraudlabspro_follow_flp_result_yes'],
                $fields->label(
                    Language::_('FraudLabsPro.settings.option_yes', true),
                    'fraudlabspro_follow_flp_result_yes'
                )
            )
        );
        $follow_flp_result->attach(
            $fields->fieldRadio(
                'fraudlabspro_follow_flp_result',
                'no',
                (isset($vars->fraudlabspro_follow_flp_result) ? $vars->fraudlabspro_follow_flp_result : null) == 'no',
                ['id' => 'fraudlabspro_follow_flp_result_no'],
                $fields->label(
                    Language::_('FraudLabsPro.settings.option_no', true),
                    'fraudlabspro_follow_flp_result_no'
                )
            )
        );
        $fields->setField($follow_flp_result);

        return $fields;
    }

    /**
     * Verifies the given data passes fraud detection
     *
     * @param array An array of key/value pairs including:
     *  - ip The user's IP address
     *  - email The user's email address
     *  - address1 The user's address line 1
     *  - address2 The user's address line 2
     *  - city The user's city
     *  - state The user's state ISO 3166-2 alpha-numeric subdivision code
     *  - country The user's country ISO 3166-1 alpha2 country code
     *  - zip The user's zip/postal code
     *  - phone The user's primary phone number
     * @return string The result of verify input, one of either:
     *  - allow Data is not fraudulent
     *  - review Data may be fraudulent, requires manual review
     *  - reject Data is fraudulent
     */
    public function verify($data)
    {
        $params = [
            'key' => $this->options['fraudlabspro_api_key'],
            'ip' => $data['ip'],
            'bill_city' => $data['city'],
            'bill_state' => $data['state'],
            'bill_zip_code' => $data['zip'],
            'bill_country' => $data['country'],
            'email_hash' => $this->hashIt($data['email']),
            'format' => 'json'
        ];

        $url  = 'https://api.fraudlabspro.com/?' . http_build_query($params);

        for ($i=0; $i<3; $i++) {
            $response = $this->getHttp($url);

            if (is_null($this->result = json_decode($response)) === false) {
                break;
            }
        }

        if (!$response || !isset($this->result->fraudlabspro_score)) {
            return 'review';
        }

        if ($this->result->fraudlabspro_score >= $this->options['fraudlabspro_reject_score']) {
            $this->setError('reject', 'reject_score');
            return 'reject';
        }

        if ($this->result->fraudlabspro_score >= $this->options['fraudlabspro_review_score']) {
            $this->setError('review', 'fraudlabspro');
            return 'review';
        }

        if ($this->result->fraudlabspro_status == 'REJECT'
            && $this->options['fraudlabspro_follow_flp_result'] == 'yes') {
            $this->setError('reject', 'fraudlabspro');
            return 'reject';
        }

        return 'allow';
    }

    /**
     * Queries FraudLabs Pro API Gateway
     *
     * @param $url The URL to query
     * @return string The result from FraudLabs Pro API gateway
     */
    private function getHttp($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Blesta-' . BLESTA_VERSION);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Returns fraud details to store for the last verify request
     *
     * @return array An array of key/value pairs
     * @see FraudDetect::verify()
     */
    public function fraudDetails()
    {
        $details = [];

        foreach ((array)$this->result as $key => $value) {
            if (is_scalar($value)) {
                $details[$key] = utf8_encode($value);
            }
        }

        return $details;
    }

    /**
     * Hashes inputs to prevent data visible by public
     *
     * @param string $s The string to hash
     * @param string $prefix The salt
     * @return string An sha1 hash value
     */
    private function hashIt($s, $prefix = 'fraudlabspro_')
    {
        $hash = $prefix . $s;
        for ($i=0; $i<65536; $i++) {
            $hash = sha1($prefix . $hash);
        }

        return $hash;
    }

    /**
     * Sets an Input error
     *
     * @param string $status The status of the verify request (review or reject)
     * @param string $type The type of error
     */
    private function setError($status, $type)
    {
        $this->Input->setErrors([
            $status => [
                'reason' => Language::_('FraudLabsPro.!error.' . $status . '.' . $type, true)
            ]
        ]);
    }
}
