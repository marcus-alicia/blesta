<?php
namespace Blesta\Core\Util\Captcha\Captchas;

use Blesta\Core\Util\Captcha\Common\AbstractCaptcha;
use Gregwar\Captcha\CaptchaBuilder;
use RuntimeException;
use Configure;
use Language;
use Loader;

/**
 * Internal captcha integration
 *
 * @package blesta
 * @subpackage blesta.core.Util.Captcha.Captchas
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InternalCaptcha extends AbstractCaptcha
{
    /**
     * @var array An array of options
     */
    private $options = [];

    /**
     * Initialize internal captcha
     */
    public function __construct()
    {
        parent::__construct();

        // Autoload the language file
        Language::loadLang(
            'internal_captcha',
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
        return Language::_('InternalCaptcha.name', true);
    }

    /**
     * Builds the HTML content to render the reCaptcha
     *
     * @return string The HTML
     */
    public function buildHtml()
    {
        Loader::loadComponents($this, ['Session']);

        // Build the captcha
        $builder = new CaptchaBuilder();
        $builder->build();

        // Store the generated phrase in the current session
        $phrase = $builder->getPhrase();
        $this->Session->write('phrase', $phrase);

        $html = '
<div class="captcha card">
    <div class="card-body">
        <img src="' . $builder->inline() . '" class="rounded mb-2" style="height: 50px;" />
        <input type="text" name="phrase" value="" class="form-control" placeholder="' .
                    Language::_('InternalCaptcha.html.field_phrase', true) . '">
    </div>
</div>';

        return $html;
    }

    /**
     * Sets internal captcha options
     *
     * @param array $options An array of options including:
     *
     *  - lang The user's language (e.g. "en_us" for English)
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
     *  - response The value of 'phrase' in the submitted form
     * @return bool Whether or not the captcha is valid
     */
    public function verify(array $data)
    {
        Loader::loadComponents($this, ['Session']);

        $success = false;

        // Attempt to verify the captcha was accepted
        try {
            $phrase = $this->Session->read('phrase');
            $success = (isset($data['response'])
                    ? $data['response']
                    : (isset($data['phrase']) ? $data['phrase'] : null)
                ) === $phrase;
        } catch (RuntimeException $e) {
            // Internal captcha could not process the request due to missing data
            $this->setErrors(['internalcaptcha' => ['error' => $e->getMessage()]]);
        }

        return $success;
    }
}
