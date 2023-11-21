<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Option;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractItemFormatter;
use stdClass;

/**
 * Option formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Option
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OptionFormatter extends AbstractItemFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function format(stdClass $fields)
    {
        return $this->make('packageOption', $fields);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function formatService(stdClass $fields)
    {
        return $this->make('serviceOption', $fields);
    }
}
