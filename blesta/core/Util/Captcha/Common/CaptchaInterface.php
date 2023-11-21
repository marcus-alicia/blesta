<?php
namespace Blesta\Core\Util\Captcha\Common;

/**
 * Captcha interface
 *
 * @package blesta
 * @subpackage blesta.core.Util.Captcha.Common
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface CaptchaInterface
{
    /**
     * Builds the HTML content to render the captcha
     *
     * @return string The HTML
     */
    public function buildHtml();

    /**
     * Retrieves any errors associated with the captcha
     *
     * @return array An array of errors
     */
    public function errors();

    /**
     * Sets options for the captcha
     *
     * @param array $options An array of options
     */
    public function setOptions(array $options);

    /**
     * Verifies whether or not the captcha is valid
     *
     * @param array $data An array of data to validate against
     * @return bool Whether or not the captcha is valid
     */
    public function verify(array $data);
}
