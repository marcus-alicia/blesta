<?php
namespace Blesta\Core\Util\Captcha\Captchas;

use Blesta\Core\Util\Captcha\Common\AbstractCaptcha;
use Blesta\Core\Util\Input\Fields\InputFields;
use Language;
use Configure;

/**
 * hCaptcha integration
 *
 * @package blesta
 * @subpackage blesta.core.Util.Captcha.Captchas
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class HCaptcha extends AbstractCaptcha
{
    /**
     * @var array An array of options
     */
    private $options = [];

    /**
     * @var string The hCaptcha JavaScript API URL
     */
    private $js_api = 'https://hcaptcha.com/1/api.js';

    /**
     * @var string The hCaptcha verification API URL
     */
    private $verify_api = 'https://hcaptcha.com/siteverify';

    /**
     * Initialize hCaptcha
     */
    public function __construct()
    {
        parent::__construct();

        // Autoload the language file
        Language::loadLang(
            'h_captcha',
            $this->Html->safe(
                ($this->options['lang'] ?? ''),
                Configure::get('Blesta.language')
            ),
            COREDIR . 'Util' . DS . 'Captcha' . DS . 'Captchas' . DS . 'language' . DS
        );
    }

    /**
     * Returns the name of the captcha provider
     *
     * @return string The name of the captcha provider
     */
    public function getName()
    {
        return Language::_('HCaptcha.name', true);
    }

    /**
     * Builds the HTML content to render the reCaptcha
     *
     * @return string The HTML
     */
    public function buildHtml()
    {
        $key = $this->Html->safe((isset($this->options['site_key']) ? $this->options['site_key'] : null));
        $lang = $this->Html->safe((isset($this->options['lang']) ? $this->options['lang'] : null));
        $api = $this->Html->safe($this->js_api . (!empty($lang) ? '?hl=' . $lang : ''));

        $html = <<< HTML
<div class="h-captcha" data-sitekey="$key"></div>
<script src="$api" async defer></script>
HTML;

        return $html;
    }

    /**
     * Sets hCaptcha options
     *
     * @param array $options An array of options including:
     *
     *  - site_key The hCaptcha site key
     *  - secret_key The hCaptcha secret key
     *  - lang The user's language (e.g. "en" for English)
     *  - ip_address The user's IP address (optional)
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Verifies whether or not the captcha is valid
     *
     * @param array $data An array of data to validate against, including:
     *
     *  - response The value of 'h-captcha-response' in the submitted form
     * @return bool Whether or not the captcha is valid
     */
    public function verify(array $data)
    {
        $success = false;

        // Attempt to verify the captcha was accepted
        $data = [
            'secret' => (isset($this->options['secret_key']) ? $this->options['secret_key'] : null),
            'response' => (isset($data['response'])
                ? $data['response']
                : (isset($data['h-captcha-response']) ? $data['h-captcha-response'] : null)
            )
        ];

        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, $this->verify_api);
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($verify));
        curl_close($verify);

        if (isset($response->success)) {
            $success = (bool) $response->success;
        }

        return $success;
    }

    /**
     * Gets a list of the options input fields
     *
     * @param array $vars An array containing the posted fields
     * @return InputFields An object representing the list of input fields
     */
    public function getOptionFields(array $vars = [])
    {
        // Set captcha option fields
        $fields = new InputFields();

        // Set site key
        $site_key = $fields->label(
            Language::_('HCaptcha.options.field_hcaptcha_site_key', true),
            'hcaptcha_site_key'
        );
        $site_key->attach(
            $fields->fieldText(
                'hcaptcha_site_key',
                isset($vars['hcaptcha_site_key']) ? $vars['hcaptcha_site_key'] : null,
                [
                    'id' => 'hcaptcha_site_key',
                    'class' => 'form-control'
                ]
            )
        );
        $fields->setField($site_key);

        // Set secret key
        $secret_key = $fields->label(
            Language::_('HCaptcha.options.field_hcaptcha_secret_key', true),
            'hcaptcha_secret_key'
        );
        $secret_key->attach(
            $fields->fieldText(
                'hcaptcha_secret_key',
                isset($vars['hcaptcha_secret_key']) ? $vars['hcaptcha_secret_key'] : null,
                [
                    'id' => 'hcaptcha_secret_key',
                    'class' => 'form-control'
                ]
            )
        );
        $fields->setField($secret_key);

        return $fields;
    }
}
