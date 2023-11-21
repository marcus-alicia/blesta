<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description\Type;

use Blesta\Items\Collection\ItemCollection;
use Minphp\Date\Date;
use Language;
use Loader;

/**
 * Abstract class for descriptions
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description.Type
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractDescription implements DescriptionInterface
{
    /**
     * @var Minphp\Date\Date An instance of the date object
     */
    protected $date;

    /**
     * @var array An array of options used to construct the presenter
     */
    protected $options;

    /**
     * Init
     *
     * @param \Minphp\Date\Date An instance of the Date object
     * @param array An array of options used to construct the presenter
     */
    public function __construct(Date $date, array $options = [])
    {
        $this->date = $date;
        $this->options = $options;

        // Autoload the language file
        $class = explode('\\', get_class($this));
        Language::loadLang(
            [Loader::fromCamelCase(end($class))],
            null,
            dirname(__FILE__) . DS . '..' . DS . 'language' . DS
        );
    }

    /**
     * Shortcut for Language::_()
     *
     * @param string $name The name of the language key to fetch
     */
    // @codingStandardsIgnoreStart
    protected function _($name)
    {
        // @codingStandardsIgnoreEnd
        $args = func_get_args();
        $first = array_shift($args);
        array_unshift($args, $first, true);

        return call_user_func_array(['Language', '_'], $args);
    }

    /**
     * Retrieves a set of proration meta fields used in the descriptions
     *
     * @param array $meta An array of meta information
     * @return array An array of prorate fields
     */
    protected function getProrateFields(array $meta)
    {
        $prorated = isset($meta['_data']['prorated']) ? $meta['_data']['prorated'] : false;
        $prorateStartDate = '';
        $prorateEndDate = '';

        if (isset($meta['prorate']) && is_object($meta['prorate'])) {
            $prorateStartDate = !empty($meta['prorate']->startDate)
                ? $this->date->cast($meta['prorate']->startDate, 'date')
                : '';
            $prorateEndDate = !empty($meta['prorate']->endDate)
                ? $this->date->cast($meta['prorate']->endDate, 'date')
                : '';
        }

        return compact('prorated', 'prorateStartDate', 'prorateEndDate');
    }
}
