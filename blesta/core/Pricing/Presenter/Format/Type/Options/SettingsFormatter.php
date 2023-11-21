<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Options;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractArrayFormatter;

/**
 * Settings formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Options
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SettingsFormatter extends AbstractArrayFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function format(array $fields)
    {
        return $this->makeItem($fields);
    }
}
