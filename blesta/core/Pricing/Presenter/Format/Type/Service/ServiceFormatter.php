<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Service;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractItemFormatter;
use stdClass;

/**
 * Service formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceFormatter extends AbstractItemFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function format(stdClass $fields)
    {
        return $this->make('serviceData', $fields);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function formatService(stdClass $fields)
    {
        return $this->make('service', $fields);
    }
}
