<?php
/**
 * Maxmind Fraud Detection
 *
 * @package blesta
 * @subpackage blesta.plugins.order.components.antifraud.maxmind
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Maxmind implements FraudDetect
{
    /**
     * @var array Key/value pair options
     */
    private $options = [];
    /**
     * @var stdClass A stdClass object representing the last API response
     */
    private $last_response = null;

    /**
     * Sets key/value pair options for initializing the fraud detection
     *
     * @param array An array of key/value pairs including:
     *  - maxmind_server The maxmind minFraud server (minfraud.maxmind.com, or minfraud-us-east.maxmind.com, etc.)
     *  - maxmind_key The maxmind license key
     *  - maxmind_reject_score The minimum score to trigger reject
     *  - maxmind_review_score The minimum score to trigger review
     *  - maxmind_free_email The action to perform if using a free email:
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_country_mismatch The action to perform if there is a country mismatch
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_risky_country The action to perform if the country is risky
     *      - allow
     *      - reject
     *      - review
     *  - maxmind_anon_proxy The action to perform if the user is behind an anonymous proxy
     *      - allow
     *      - reject
     *      - review
     */
    public function __construct(array $options)
    {
        Language::loadLang('maxmind', null, dirname(__FILE__) . DS . 'language' . DS);
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

        // Server
        $server = $fields->label(Language::_('Maxmind.settings.field_server', true), 'maxmind_server');
        $fields->setField(
            $server->attach(
                $fields->fieldText(
                    'maxmind_server',
                    (isset($vars->maxmind_server) ? $vars->maxmind_server : 'minfraud.maxmind.com'),
                    ['id' => 'maxmind_server']
                )
            )
        );

        // License Key
        $key = $fields->label(Language::_('Maxmind.settings.field_key', true), 'maxmind_key');
        $fields->setField(
            $key->attach(
                $fields->fieldText('maxmind_key', (isset($vars->maxmind_key) ? $vars->maxmind_key : null), ['id' => 'maxmind_key'])
            )
        );

        // Reject Score
        $reject_score = $fields->label(
            Language::_('Maxmind.settings.field_reject_score', true),
            'maxmind_reject_score'
        );
        $fields->setField(
            $reject_score->attach(
                $fields->fieldText(
                    'maxmind_reject_score',
                    (isset($vars->maxmind_reject_score) ? $vars->maxmind_reject_score : '80'),
                    ['id' => 'maxmind_reject_score']
                )
            )
        );

        // Review Score
        $review_score = $fields->label(
            Language::_('Maxmind.settings.field_review_score', true),
            'maxmind_review_score'
        );
        $fields->setField(
            $review_score->attach(
                $fields->fieldText(
                    'maxmind_review_score',
                    (isset($vars->maxmind_review_score) ? $vars->maxmind_review_score : '10'),
                    ['id' => 'maxmind_review_score']
                )
            )
        );

        // Free Email
        $free_email = $fields->label(Language::_('Maxmind.settings.field_free_email', true), 'maxmind_free_email');
        $free_email->attach(
            $fields->fieldRadio(
                'maxmind_free_email',
                'allow',
                (isset($vars->maxmind_free_email) ? $vars->maxmind_free_email : 'allow') == 'allow',
                ['id' => 'maxmind_free_email_allow'],
                $fields->label(Language::_('Maxmind.settings.option_allow', true), 'maxmind_free_email_allow')
            )
        );
        $free_email->attach(
            $fields->fieldRadio(
                'maxmind_free_email',
                'review',
                (isset($vars->maxmind_free_email) ? $vars->maxmind_free_email : null) == 'review',
                ['id' => 'maxmind_free_email_review'],
                $fields->label(Language::_('Maxmind.settings.option_review', true), 'maxmind_free_email_review')
            )
        );
        $free_email->attach(
            $fields->fieldRadio(
                'maxmind_free_email',
                'reject',
                (isset($vars->maxmind_free_email) ? $vars->maxmind_free_email : null) == 'reject',
                ['id' => 'maxmind_free_email_reject'],
                $fields->label(Language::_('Maxmind.settings.option_reject', true), 'maxmind_free_email_reject')
            )
        );
        $fields->setField($free_email);

        // Country Mismatch
        $country_mismatch = $fields->label(
            Language::_('Maxmind.settings.field_country_mismatch', true),
            'maxmind_country_mismatch'
        );
        $country_mismatch->attach(
            $fields->fieldRadio(
                'maxmind_country_mismatch',
                'allow',
                (isset($vars->maxmind_country_mismatch) ? $vars->maxmind_country_mismatch : 'allow') == 'allow',
                ['id' => 'maxmind_country_mismatch_allow'],
                $fields->label(Language::_('Maxmind.settings.option_allow', true), 'maxmind_country_mismatch_allow')
            )
        );
        $country_mismatch->attach(
            $fields->fieldRadio(
                'maxmind_country_mismatch',
                'review',
                (isset($vars->maxmind_country_mismatch) ? $vars->maxmind_country_mismatch : null) == 'review',
                ['id' => 'maxmind_country_mismatch_review'],
                $fields->label(Language::_('Maxmind.settings.option_review', true), 'maxmind_country_mismatch_review')
            )
        );
        $country_mismatch->attach(
            $fields->fieldRadio(
                'maxmind_country_mismatch',
                'reject',
                (isset($vars->maxmind_country_mismatch) ? $vars->maxmind_country_mismatch : null) == 'reject',
                ['id' => 'maxmind_country_mismatch_reject'],
                $fields->label(Language::_('Maxmind.settings.option_reject', true), 'maxmind_country_mismatch_reject')
            )
        );
        $fields->setField($country_mismatch);

        // Risky Country
        $risky_country = $fields->label(
            Language::_('Maxmind.settings.field_risky_country', true),
            'maxmind_risky_country'
        );
        $risky_country->attach(
            $fields->fieldRadio(
                'maxmind_risky_country',
                'allow',
                (isset($vars->maxmind_risky_country) ? $vars->maxmind_risky_country : 'allow') == 'allow',
                ['id' => 'maxmind_risky_country_allow'],
                $fields->label(Language::_('Maxmind.settings.option_allow', true), 'maxmind_risky_country_allow')
            )
        );
        $risky_country->attach(
            $fields->fieldRadio(
                'maxmind_risky_country',
                'review',
                (isset($vars->maxmind_risky_country) ? $vars->maxmind_risky_country : null) == 'review',
                ['id' => 'maxmind_risky_country_review'],
                $fields->label(Language::_('Maxmind.settings.option_review', true), 'maxmind_risky_country_review')
            )
        );
        $risky_country->attach(
            $fields->fieldRadio(
                'maxmind_risky_country',
                'reject',
                (isset($vars->maxmind_risky_country) ? $vars->maxmind_risky_country : null) == 'reject',
                ['id' => 'maxmind_risky_country_reject'],
                $fields->label(Language::_('Maxmind.settings.option_reject', true), 'maxmind_risky_country_reject')
            )
        );
        $fields->setField($risky_country);

        // Anonymous Proxy
        $anon_proxy = $fields->label(Language::_('Maxmind.settings.field_anon_proxy', true), 'maxmind_anon_proxy');
        $anon_proxy->attach(
            $fields->fieldRadio(
                'maxmind_anon_proxy',
                'allow',
                (isset($vars->maxmind_anon_proxy) ? $vars->maxmind_anon_proxy : 'allow') == 'allow',
                ['id' => 'maxmind_anon_proxy_allow'],
                $fields->label(Language::_('Maxmind.settings.option_allow', true), 'maxmind_anon_proxy_allow')
            )
        );
        $anon_proxy->attach(
            $fields->fieldRadio(
                'maxmind_anon_proxy',
                'review',
                (isset($vars->maxmind_anon_proxy) ? $vars->maxmind_anon_proxy : null) == 'review',
                ['id' => 'maxmind_anon_proxy_review'],
                $fields->label(Language::_('Maxmind.settings.option_review', true), 'maxmind_anon_proxy_review')
            )
        );
        $anon_proxy->attach(
            $fields->fieldRadio(
                'maxmind_anon_proxy',
                'reject',
                (isset($vars->maxmind_anon_proxy) ? $vars->maxmind_anon_proxy : null) == 'reject',
                ['id' => 'maxmind_anon_proxy_reject'],
                $fields->label(Language::_('Maxmind.settings.option_reject', true), 'maxmind_anon_proxy_reject')
            )
        );
        $fields->setField($anon_proxy);

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
        $ccv2r = $this->loadApi('ccv2r');

        $input = [
            'license_key' => $this->options['maxmind_key'],
            'i' => $data['ip'],
            'city' => $data['city'],
            'region' => $data['state'],
            'postal' => $data['zip'],
            'country' => $data['country'],
            'emailMD5' => md5($data['email']),
            'domain' => ltrim(strstr($data['email'], '@'), '@')
        ];

        $this->last_response = $response = $ccv2r->request($input);
        if (($error = $response->errors())) {
            // Nothing to do
        }

        $status = 'allow';
        $result = $response->response();

        if (!$result || !isset($result->riskScore)) {
            return $status;
        }

        if ($result->riskScore >= $this->options['maxmind_reject_score']) {
            $this->setError('reject', 'reject_score');
            return 'reject';
        }

        if ($result->riskScore >= $this->options['maxmind_review_score']) {
            $this->setError('review', 'review_score');
            $status = 'review';
        }

        if ($status != 'reject' && $result->freeMail == 'Yes' && $this->options['maxmind_free_email'] != 'allow') {
            $this->setError($this->options['maxmind_free_email'], 'free_email');
            $status = $this->options['maxmind_free_email'];
        }

        if ($status != 'reject' && $result->countryMatch == 'No'
            && $this->options['maxmind_country_mismatch'] != 'allow') {
            $this->setError($this->options['maxmind_country_mismatch'], 'country_mismatch');
            $status = $this->options['maxmind_country_mismatch'];
        }

        if ($status != 'reject' && $result->highRiskCountry == 'Yes'
            && $this->options['maxmind_risky_country'] != 'allow') {
            $this->setError($this->options['maxmind_risky_country'], 'risky_country');
            $status = $this->options['maxmind_risky_country'];
        }

        if ($status != 'reject' && $result->anonymousProxy == 'Yes'
            && $this->options['maxmind_anon_proxy'] != 'allow') {
            $this->setError($this->options['maxmind_anon_proxy'], 'anon_proxy');
            $status = $this->options['maxmind_anon_proxy'];
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

        if ($this->last_response) {
            foreach ($this->last_response->response() as $key => $value) {
                if (is_scalar($value)) {
                    $response[$key] = utf8_encode($value);
                }
            }
        }
        return $response;
    }

    /**
     * Loads the given API command
     *
     * @param $command The command to load
     * @return object An object representing the API command
     */
    private function loadApi($command)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'maxmind_api.php');
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'commands' . DS . 'maxmind_' . $command . '.php');

        $api = new MaxmindApi($this->options['maxmind_server']);
        $command = Loader::toCamelCase('maxmind_' . $command);
        return new $command($api);
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
            [$status => ['reason' => Language::_('Maxmind.!error.' . $status . '.' . $type, true)]]
        );
    }
}
