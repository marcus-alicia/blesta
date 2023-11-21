<?php
namespace Blesta\Core\Util\Captcha;

use Blesta\Core\Util\Captcha\Captchas\InternalCaptcha;
use Blesta\Core\Util\Captcha\Captchas\ReCaptcha;
use Blesta\Core\Util\Captcha\Captchas\HCaptcha;

/**
 * Captcha Factory
 *
 * Creates new captcha instances
 *
 * @package blesta
 * @subpackage blesta.core.Util.Captcha.Captchas
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CaptchaFactory
{
    /**
     * Creates an instance of the provided 3rd party captcha
     */
    public function __call($name, $arguments)
    {
        $class = 'Blesta\\Core\\Util\\Captcha\\Captchas\\' . $name;

        if (class_exists($class)) {
            $captcha = new $class();

            if (!empty($arguments)) {
                call_user_func_array([$captcha, 'setOptions'], $arguments);
            }

            return $captcha;
        }
    }

    /**
     * Creates an instance of Google reCaptcha
     *
     * @param array $options An array of options including:
     *
     *  - site_key The reCaptcha site key
     *  - shared_key The reCaptcha shared key
     *  - lang The user's language (e.g. "en" for English)
     *  - ip_address The user's IP address (optional)
     */
    public function reCaptcha(array $options = [])
    {
        $recaptcha = new ReCaptcha();
        $recaptcha->setOptions($options);

        return $recaptcha;
    }

    /**
     * Creates an instance of Internal Captcha
     *
     * @param array $options An array of options including:
     *
     *  - lang The user's language (e.g. "en_us" for English)
     *  - ip_address The user's IP address (optional)
     */
    public function internalCaptcha(array $options = [])
    {
        $internalcaptcha = new InternalCaptcha();
        $internalcaptcha->setOptions($options);

        return $internalcaptcha;
    }

    /**
     * Creates an instance of hCaptcha
     *
     * @param array $options An array of options including:
     *
     *  - site_key The hCaptcha site key
     *  - secret_key The hCaptcha secret key
     *  - lang The user's language (e.g. "en" for English)
     *  - ip_address The user's IP address (optional)
     */
    public function hCaptcha(array $options = [])
    {
        $hcaptcha = new HCaptcha();
        $hcaptcha->setOptions($options);

        return $hcaptcha;
    }
}
