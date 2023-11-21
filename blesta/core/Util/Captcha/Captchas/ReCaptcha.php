<?php
namespace Blesta\Core\Util\Captcha\Captchas;

use Blesta\Core\Util\Captcha\Common\AbstractCaptcha;
use Blesta\Core\Util\Input\Fields\InputFields;
use RuntimeException;
use Language;
use Configure;

/**
 * Google reCAPTCHA integration
 *
 * @package blesta
 * @subpackage blesta.core.Util.Captcha.Captchas
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ReCaptcha extends AbstractCaptcha
{
    /**
     * @var array An array of options
     */
    private $options = [];

    /**
     * @var string The Google reCaptcha JavaScript API URL
     */
    private $apiUrl = 'https://www.google.com/recaptcha/api.js';

    /**
     * Initialize ReCaptcha
     */
    public function __construct()
    {
        parent::__construct();

        // Autoload the language file
        Language::loadLang(
            're_captcha',
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
        return Language::_('ReCaptcha.name', true);
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
        $apiUrl = $this->Html->safe($this->apiUrl . (!empty($lang) ? '?hl=' . $lang : ''));

        $html = <<< HTML
<div class="g-recaptcha" data-sitekey="$key"></div>
<script type="text/javascript" src="$apiUrl"></script>
HTML;

        return $html;
    }

    /**
     * Sets reCaptcha options
     *
     * @param array $options An array of options including:
     *
     *  - site_key The reCaptcha site key
     *  - shared_key The reCaptcha shared key
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
     *  - response The value of 'g-recaptcha-response' in the submitted form
     * @return bool Whether or not the captcha is valid
     */
    public function verify(array $data)
    {
        $success = false;

        // Attempt to verify the captcha was accepted
        try {
            if (in_array(ini_get('allow_url_fopen'), ['Off', '0', 0, ''])) {
                $recaptcha = new \ReCaptcha\ReCaptcha(
                    $this->options['shared_key'] ?? null,
                    new \ReCaptcha\RequestMethod\SocketPost()
                );
            } else {
                $recaptcha = new \ReCaptcha\ReCaptcha($this->options['shared_key'] ?? null);
            }

            $response = $recaptcha->verify(
                ($data['response'] ?? ($data['g-recaptcha-response'] ?? null)),
                ($data['ip_address'] ?? null)
            );

            $success = $response->isSuccess();
        } catch (RuntimeException $e) {
            // ReCaptcha could not process the request due to missing data
            $this->setErrors(['recaptcha' => ['error' => $e->getMessage()]]);
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

        // Set pub key
        $pub_key = $fields->label(
            Language::_('ReCaptcha.options.field_recaptcha_pub_key', true),
            'recaptcha_pub_key'
        );
        $pub_key->attach(
            $fields->fieldText(
                'recaptcha_pub_key',
                isset($vars['recaptcha_pub_key']) ? $vars['recaptcha_pub_key'] : null,
                [
                    'id' => 'recaptcha_pub_key',
                    'class' => 'form-control'
                ]
            )
        );
        $fields->setField($pub_key);

        // Set shared key
        $shared_key = $fields->label(
            Language::_('ReCaptcha.options.field_recaptcha_shared_key', true),
            'recaptcha_shared_key'
        );
        $shared_key->attach(
            $fields->fieldText(
                'recaptcha_shared_key',
                isset($vars['recaptcha_shared_key']) ? $vars['recaptcha_shared_key'] : null,
                [
                    'id' => 'recaptcha_shared_key',
                    'class' => 'form-control'
                ]
            )
        );
        $fields->setField($shared_key);

        return $fields;
    }
}
