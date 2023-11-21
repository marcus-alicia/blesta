<?php
namespace Blesta\Core\Util\Captcha;

use Blesta\Core\Util\Captcha\Common\CaptchaInterface;
use Blesta\Core\Util\Common\Traits\Container;
use Loader;
use Configure;
use Exception;
use stdClass;

/**
 * Captcha Utility
 *
 * @package blesta
 * @subpackage blesta.core.Util.Captcha
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Captcha
{
    // Include traits
    use Container;

    /**
     * Retrieve an instance of the captcha
     *
     * @param string $captcha The name of the captcha class to initialize
     * @param array $options The options to be used with the captcha
     * @return Blesta\Core\Util\Captcha\Common\CaptchaInterface
     */
    public static function get($captcha = null, array $options = [])
    {
        $parent = new stdClass();

        Loader::loadModels($parent, ['Companies']);
        Loader::loadComponents($parent, ['SettingsCollection']);

        // Get company settings
        $company_settings = $parent->SettingsCollection->fetchSettings(
            $parent->Companies,
            Configure::get('Blesta.company_id')
        );
        $captcha = is_null($captcha) ? $company_settings['captcha'] : $captcha;

        // Set default options
        if (empty($options)) {
            $options = self::getOptions($captcha);
        }

        // Initialize captcha
        $factory = new CaptchaFactory();

        if (method_exists($factory, $captcha)) {
            return $factory->{$captcha}($options);
        }

        return null;
    }

    /**
     * Validates the response of the captcha
     *
     * @param CaptchaInterface $captcha An instance of the captcha to validate
     * @param array $post An array containing the posted data
     * @return bool True if the provided response for the captcha is valid, false otherwise
     */
    public static function validate(CaptchaInterface $captcha, array $post = [])
    {
        $parent = new stdClass();

        Loader::loadModels($parent, ['Companies']);
        Loader::loadComponents($parent, ['SettingsCollection']);

        // Get company settings
        $company_settings = $parent->SettingsCollection->fetchSettings(
            $parent->Companies,
            Configure::get('Blesta.company_id')
        );

        // Validate captcha
        $success = false;
        switch ($company_settings['captcha']) {
            case 'recaptcha':
                $response = (isset($post['g-recaptcha-response']) ? $post['g-recaptcha-response'] : '');
                $success = $captcha->verify(['response' => $response]);
                break;
            case 'internalcaptcha':
                $response = (isset($post['phrase']) ? $post['phrase'] : '');
                $success = $captcha->verify(['response' => $response]);
                break;
            default:
                $success = $captcha->verify($post);
                break;
        }

        return $success;
    }

    /**
     * Checks if a form is enabled for captcha validation
     *
     * @param string $form The name of the form
     * @return bool True if the form has captcha enabled, false otherwise
     */
    public static function enabled($form)
    {
        $parent = new stdClass();

        Loader::loadModels($parent, ['Companies']);
        Loader::loadComponents($parent, ['SettingsCollection']);

        // Get company settings
        $company_settings = $parent->SettingsCollection->fetchSettings(
            $parent->Companies,
            Configure::get('Blesta.company_id')
        );

        if (isset($company_settings['captcha_enabled_forms'])) {
            $company_settings['captcha_enabled_forms'] = unserialize($company_settings['captcha_enabled_forms']);
        }
        
        $form_enabled = isset($company_settings['captcha_enabled_forms'][$form])
            ? (bool) $company_settings['captcha_enabled_forms'][$form]
            : false;

        return !empty($company_settings['captcha']) && $company_settings['captcha'] !== 'none' && $form_enabled;
    }

    /**
     * Lists all available captchas on the system
     *
     * @return array An array of stdClass objects representing available catpchas
     */
    public static function getAvailable()
    {
        $captchas = [];
        $dir = opendir(COREDIR . 'Util' . DS . 'Captcha' . DS . 'Captchas');

        while (false !== ($captcha = readdir($dir))) {
            // If the file is not a hidden file, accept it
            if (
                substr($captcha, 0, 1) != '.'
                && !is_dir(COREDIR . 'Util' . DS . 'Captcha' . DS . 'Captchas' . DS . $captcha)
            ) {
                $factory = new CaptchaFactory();
                $class = substr($captcha, 0, -4);

                try {
                    $instance = $factory->{$class}();
                    $captchas[] = (object) [
                        'name' => $instance->getName(),
                        'class' => $class,
                        'id' => strtolower($class)
                    ];
                } catch (Exception $e) {
                    // Nothing to do
                }
            }
        }

        return $captchas;
    }

    /**
     * Fetches the default options for the captcha
     *
     * @param string $captcha The name of the captcha class to fetch the options
     * @return array An array containing the default options for the captcha
     */
    public static function getOptions($captcha = null)
    {
        $parent = new Captcha();

        Loader::loadModels($parent, ['Companies']);
        Loader::loadComponents($parent, ['SettingsCollection']);

        // Get company settings
        $company_settings = $parent->SettingsCollection->fetchSettings(
            $parent->Companies,
            Configure::get('Blesta.company_id')
        );
        $requestor = $parent->getFromContainer('requestor');
        $captcha = is_null($captcha) ? $company_settings['captcha'] : $captcha;

        // Get captcha instance
        $options = $company_settings;
        switch ($captcha) {
            case 'recaptcha':
                $options = [
                    'site_key' => $company_settings['recaptcha_pub_key'],
                    'shared_key' => $company_settings['recaptcha_shared_key'],
                    'lang' => substr($company_settings['language'], 0, 2),
                    'ip_address' => $requestor->ip_address
                ];
                break;
            case 'internalcaptcha':
                $options = [
                    'lang' => $company_settings['language'],
                    'ip_address' => $requestor->ip_address
                ];
                break;
            case 'hcaptcha':
                $options = [
                    'site_key' => $company_settings['hcaptcha_site_key'],
                    'secret_key' => $company_settings['hcaptcha_secret_key'],
                    'lang' => substr($company_settings['language'], 0, 2)
                ];
                break;
            default:
                // Fetch captcha option fields
                $factory = new CaptchaFactory();
                if (($instance = $factory->{$captcha}())) {
                    $fields = $instance->getOptionFields();
                    $requested_fields = [];

                    if (!empty($fields)) {
                        foreach ($fields->getFields() as $field) {
                            if (!empty($field->fields) && $field->type == 'label') {
                                foreach ($field->fields as $sub_field) {
                                    $requested_fields[] = $sub_field->params['name'];
                                }
                            } else {
                                $requested_fields[] = $field->params['name'];
                            }
                        }

                        $options = array_filter($company_settings, function ($key) use ($requested_fields) {
                            return in_array($key, $requested_fields);
                        }, ARRAY_FILTER_USE_KEY);
                    }
                }

                // Add language and IP address
                $options['lang'] = $company_settings['language'];
                $options['ip_address'] = $requestor->ip_address;
                break;
        }

        return $options;
    }
}
