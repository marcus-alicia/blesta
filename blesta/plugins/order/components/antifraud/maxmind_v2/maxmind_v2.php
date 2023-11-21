<?php

use MaxMind\MinFraud;

/**
 * Maxmind v2 Fraud Detection
 *
 * @package blesta
 * @subpackage blesta.plugins.order.components.antifraud.maxmind_v2
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MaxmindV2 implements FraudDetect
{
    /**
     * @var array Key/value pair options
     */
    private $options = [];
    /**
     * @var stdClass A stdClass object representing the last API response
     */
    private $response = null;

    /**
     * Sets key/value pair options for initializing the fraud detection
     *
     * @param array An array of key/value pairs including:
     *
     *  - maxmind_v2_server The MaxMind minFraud server (minfraud.maxmind.com, or minfraud-us-east.maxmind.com, etc.)
     *  - maxmind_v2_account_id The MaxMind account ID
     *  - maxmind_v2_key The MaxMind license key
     *  - maxmind_v2_minfraud_api The minFraud API tier to use
     *      - score
     *      - insights
     *      - factors
     *  - maxmind_v2_reject_score The minimum score to trigger reject
     *  - maxmind_v2_review_score The minimum score to trigger review
     *  - maxmind_v2_free_email The action to perform if using a free email:
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_v2_country_mismatch The action to perform if there is a country mismatch
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_v2_risky_country The action to perform if the country is risky
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_v2_anon_proxy The action to perform if the user is behind an anonymous proxy
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_v2_private_ip The action to perform if the user is accessing from a private or reserved IP
     *      - allow
     *      - reject
     *      - review
     *  - enable_js Whether or not to include the JS code on order forms
     */
    public function __construct(array $options)
    {
        Language::loadLang('maxmind_v2', null, dirname(__FILE__) . DS . 'language' . DS);
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

        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    $(this).blestaBindToolTips();
                });
            </script>
        ");

        Loader::loadHelpers($this, ['Html']);

        // Server
        $server = $fields->label(Language::_('MaxmindV2.settings.field_server', true), 'maxmind_v2_server');
        $fields->setField(
            $server->attach(
                $fields->fieldText(
                    'maxmind_v2_server',
                    (isset($vars->maxmind_v2_server) ? $vars->maxmind_v2_server : 'minfraud.maxmind.com'),
                    ['id' => 'maxmind_v2_server']
                )
            )
        );

        // Account ID
        $key = $fields->label(Language::_('MaxmindV2.settings.field_account_id', true), 'maxmind_v2_account_id');
        $fields->setField(
            $key->attach(
                $fields->fieldText('maxmind_v2_account_id', (isset($vars->maxmind_v2_account_id) ? $vars->maxmind_v2_account_id : null), ['id' => 'maxmind_v2_account_id'])
            )
        );

        // License Key
        $key = $fields->label(Language::_('MaxmindV2.settings.field_key', true), 'maxmind_v2_key');
        $fields->setField(
            $key->attach(
                $fields->fieldText('maxmind_v2_key', (isset($vars->maxmind_v2_key) ? $vars->maxmind_v2_key : null), ['id' => 'maxmind_v2_key'])
            )
        );

        // minFraud API
        $minfraud_api = $fields->label(
            Language::_('MaxmindV2.settings.field_minfraud_api', true),
            'maxmind_v2_minfraud_api'
        );
        $minfraud_api->attach(
            $fields->fieldRadio(
                'maxmind_v2_minfraud_api',
                'score',
                (isset($vars->maxmind_v2_minfraud_api) ? $vars->maxmind_v2_minfraud_api : 'score') == 'score',
                ['id' => 'maxmind_v2_minfraud_api_score'],
                $fields->label(Language::_('MaxmindV2.settings.option_score', true), 'maxmind_v2_minfraud_api_score')
            )
        );
        $minfraud_api->attach(
            $fields->fieldRadio(
                'maxmind_v2_minfraud_api',
                'insights',
                (isset($vars->maxmind_v2_minfraud_api) ? $vars->maxmind_v2_minfraud_api : null) == 'insights',
                ['id' => 'maxmind_v2_minfraud_api_insights'],
                $fields->label(Language::_('MaxmindV2.settings.option_insights', true), 'maxmind_v2_minfraud_api_insights')
            )
        );
        $minfraud_api->attach(
            $fields->fieldRadio(
                'maxmind_v2_minfraud_api',
                'factors',
                (isset($vars->maxmind_v2_minfraud_api) ? $vars->maxmind_v2_minfraud_api : null) == 'factors',
                ['id' => 'maxmind_v2_minfraud_api_factors'],
                $fields->label(Language::_('MaxmindV2.settings.option_factors', true), 'maxmind_v2_minfraud_api_factors')
            )
        );
        $fields->setField($minfraud_api);

        // Reject Score
        $reject_score = $fields->label(
            Language::_('MaxmindV2.settings.field_reject_score', true),
            'maxmind_v2_reject_score'
        );
        $fields->setField(
            $reject_score->attach(
                $fields->fieldText(
                    'maxmind_v2_reject_score',
                    (isset($vars->maxmind_v2_reject_score) ? $vars->maxmind_v2_reject_score : '80'),
                    ['id' => 'maxmind_v2_reject_score']
                )
            )
        );

        // Review Score
        $review_score = $fields->label(
            Language::_('MaxmindV2.settings.field_review_score', true),
            'maxmind_v2_review_score'
        );
        $fields->setField(
            $review_score->attach(
                $fields->fieldText(
                    'maxmind_v2_review_score',
                    (isset($vars->maxmind_v2_review_score) ? $vars->maxmind_v2_review_score : '10'),
                    ['id' => 'maxmind_v2_review_score']
                )
            )
        );

        // Set risk fields
        $risk_fields = ['free_email', 'country_mismatch', 'risky_country', 'anon_proxy', 'private_ip'];

        foreach ($risk_fields as $risk_field) {
            $field = $fields->label(Language::_('MaxmindV2.settings.field_' . $risk_field, true), 'maxmind_v2_' . $risk_field);
            $field->attach(
                $fields->fieldRadio(
                    'maxmind_v2_' . $risk_field,
                    'allow',
                    (isset($vars->{'maxmind_v2_' . $risk_field}) ? $vars->{'maxmind_v2_' . $risk_field} : 'allow') == 'allow',
                    ['id' => 'maxmind_v2_' . $risk_field . '_allow'],
                    $fields->label(Language::_('MaxmindV2.settings.option_allow', true), 'maxmind_v2_' . $risk_field . '_allow')
                )
            );
            $field->attach(
                $fields->fieldRadio(
                    'maxmind_v2_' . $risk_field,
                    'review',
                    (isset($vars->{'maxmind_v2_' . $risk_field}) ? $vars->{'maxmind_v2_' . $risk_field} : null) == 'review',
                    ['id' => 'maxmind_v2_' . $risk_field . '_review'],
                    $fields->label(Language::_('MaxmindV2.settings.option_review', true), 'maxmind_v2_' . $risk_field . '_review')
                )
            );
            $field->attach(
                $fields->fieldRadio(
                    'maxmind_v2_' . $risk_field,
                    'reject',
                    (isset($vars->{'maxmind_v2_' . $risk_field}) ? $vars->{'maxmind_v2_' . $risk_field} : null) == 'reject',
                    ['id' => 'maxmind_v2_' . $risk_field . '_reject'],
                    $fields->label(Language::_('MaxmindV2.settings.option_reject', true), 'maxmind_v2_' . $risk_field . '_reject')
                )
            );
            $fields->setField($field);
        }

        // Enable JavaScript Tracking Add-on
        $enable_js = $fields->label(Language::_('MaxmindV2.settings.field_enable_js', true), 'enable_js');
        $enable_js->attach(
            $fields->fieldRadio(
                'enable_js',
                'enable',
                (isset($vars->enable_js) ? $vars->enable_js : 'enable') == 'enable',
                ['id' => 'enable_js_enable'],
                $fields->label(Language::_('MaxmindV2.settings.option_enable', true), 'enable_js_enable')
            )
        );
        $enable_js->attach(
            $fields->fieldRadio(
                'enable_js',
                'disable',
                (isset($vars->enable_js) ? $vars->enable_js : null) == 'disable',
                ['id' => 'enable_js_disable'],
                $fields->label(Language::_('MaxmindV2.settings.option_disable', true), 'enable_js_disable')
            )
        );
        $tooltip = $fields->tooltip(Language::_('MaxmindV2.settings.tooltip.enable_js', true));
        $enable_js->attach($tooltip);
        $fields->setField($enable_js);

        return $fields;
    }

    /**
     * Verifies the given data passes fraud detection
     *
     * @param array An array of key/value pairs including:
     *
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
     *
     *  - allow Data is not fraudulent
     *  - review Data may be fraudulent, requires manual review
     *  - reject Data is fraudulent
     */
    public function verify($data)
    {
        $this->MinFraud = new MinFraud(
            $this->options['maxmind_v2_account_id'],
            $this->options['maxmind_v2_key'],
            ['host' => $this->options['maxmind_v2_server']]
        );

        $response = $this->MinFraud->withDevice([
            'ip_address' => $data['ip'],
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? utf8_encode($_SERVER['HTTP_USER_AGENT']) : null
        ])->withEmail([
            'address' => utf8_encode($data['email']),
            'domain' => utf8_encode(ltrim(strstr($data['email'], '@'), '@'))
        ])->withBilling([
            'address' => utf8_encode($data['address1']),
            'address_2' => utf8_encode($data['address2']),
            'city' => utf8_encode($data['city']),
            'region' => utf8_encode($data['state']),
            'country' => utf8_encode($data['country']),
            'postal' => utf8_encode($data['zip']),
            'phone_number' => utf8_encode(ltrim($data['phone'], '+'))
        ]);

        $status = 'allow';
        try {
            switch ($this->options['maxmind_v2_minfraud_api']) {
                case 'insights':
                    $this->response = $response->insights();
                    break;
                case 'factors':
                    $this->response = $response->factors();
                    break;
                case 'score':
                default:
                    $this->response = $response->score();
                    break;
            }
        } catch (MaxMind\Exception\IpAddressNotFoundException $e) {
            // MaxMind does not allow API queries when a private IP is sent (such as 127.0.0.1 or ::1)
            // Therefore we catch the exception here and make a decision according to the set options
            if (isset($this->options['maxmind_v2_private_ip'])) {
                $status = $this->options['maxmind_v2_private_ip'];

                if ($status !== 'allow') {
                    $this->setError($status, 'private_ip');
                }
            }
        }

        if (!isset($this->response) || !isset($this->response->riskScore)) {
            return $status;
        }

        // Evaluate reject and review score, available on all minFraud versions
        if (isset($this->options['maxmind_v2_reject_score']) && $this->response->riskScore >= $this->options['maxmind_v2_reject_score']) {
            $this->setError('reject', 'reject_score');
            return 'reject';
        }

        if (isset($this->options['maxmind_v2_review_score']) && $this->response->riskScore >= $this->options['maxmind_v2_review_score']) {
            $this->setError('review', 'review_score');
            $status = 'review';
        }

        // Evaluate the additional conditions only available in minFraud Insights and minFraud Factors
        if ($this->options['maxmind_v2_minfraud_api'] !== 'score' && $status != 'reject') {
            if (
                (bool)$this->response->rawResponse['email']['is_free']
                && $this->options['maxmind_v2_free_email'] != 'allow'
            ) {
                $this->setError($this->options['maxmind_v2_free_email'], 'free_email');
                $status = $this->options['maxmind_v2_free_email'];
            }

            if (
                !(bool)$this->response->rawResponse['billing_address']['is_in_ip_country']
                && $this->options['maxmind_v2_country_mismatch'] != 'allow'
            ) {
                $this->setError($this->options['maxmind_v2_country_mismatch'], 'country_mismatch');
                $status = $this->options['maxmind_v2_country_mismatch'];
            }

            if (
                (bool)$this->response->rawResponse['ip_address']['country']['is_high_risk']
                && $this->options['maxmind_v2_risky_country'] != 'allow'
            ) {
                $this->setError($this->options['maxmind_v2_risky_country'], 'risky_country');
                $status = $this->options['maxmind_v2_risky_country'];
            }

            if (
                (bool)$this->response->rawResponse['ip_address']['traits']['is_anonymous']
                && $this->options['maxmind_v2_anon_proxy'] != 'allow'
            ) {
                $this->setError($this->options['maxmind_v2_anon_proxy'], 'anon_proxy');
                $status = $this->options['maxmind_v2_anon_proxy'];
            }
        }

        return $status;
    }

    /**
     * Returns fraud details to store for the last verify request
     *
     * @return array An array of key/value pairs
     * @see FraudDetect::verify()
     */
    public function fraudDetails()
    {
        $response = [];

        if ($this->response) {
            foreach ($this->response->rawResponse as $key => $value) {
                if (is_scalar($value)) {
                    $response[$key] = utf8_encode($value);
                } else {
                    $response[$key] = json_encode($value, JSON_PRETTY_PRINT);
                }
            }
        }
        return $response;
    }

    /**
     * Returns the javascript code of the tracking add-on for minFraud
     *
     * @return string The MaxMind fraud javascript code
     */
    public function getJavascript()
    {
        return '<script type="text/javascript">
            maxmind_user_id = "' . $this->options['maxmind_v2_account_id'] . '";
            (function() {
                var loadDeviceJs = function() {
                    var element = document.createElement(\'script\');
                    element.src = \'https://device.maxmind.com/js/device.js\';
                    document.body.appendChild(element);
                };
                if (window.addEventListener) {
                    window.addEventListener(\'load\', loadDeviceJs, false);
                } else if (window.attachEvent) {
                    window.attachEvent(\'onload\', loadDeviceJs);
                }
            })();
        </script>';
    }

    /**
     * Sets an Input error
     *
     * @param string $status The status of the verify request (review or reject)
     * @param string $type The type of error
     */
    private function setError($status, $type)
    {
        $this->Input->setErrors(
            [$status => ['reason' => Language::_('MaxmindV2.!error.' . $status . '.' . $type, true)]]
        );
    }
}
