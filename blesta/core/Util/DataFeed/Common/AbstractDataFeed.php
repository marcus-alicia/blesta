<?php
namespace Blesta\Core\Util\DataFeed\Common;

use Minphp\Html\Html;

/**
 * Abstract data feed
 *
 * @package blesta
 * @subpackage blesta.core.Util.DataFeed.Common
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractDataFeed implements DataFeedInterface
{
    /**
     * @var Minphp\Html\Html An instance of Html
     */
    protected $Html;

    /**
     * @var array An array of errors
     */
    private $errors = [];

    /**
     * Init
     */
    public function __construct()
    {
        $this->Html = new Html();
    }

    /**
     * Retrieves any errors associated with the captcha
     *
     * @return array An array of errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Sets the given errors
     *
     * @param array $errors An array of error messages in the format:
     *
     *  - ['name' => ['type' => 'Error Message']]
     */
    protected function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Gets a list of the options input fields
     *
     * @param array $vars An array containing the posted fields
     * @return InputFields An object representing the list of input fields
     */
    public function getOptionFields(array $vars = [])
    {
        return null;
    }
}
