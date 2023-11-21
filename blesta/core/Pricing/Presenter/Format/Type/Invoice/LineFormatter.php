<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Invoice;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractItemFormatter;
use stdClass;

class LineFormatter extends AbstractItemFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return Blesta\Items\Item\Item An Item instance
     */
    public function format(stdClass $fields)
    {
        return $this->make('invoiceLineData', $fields);
    }

    /**
     * {@inheritdoc}
     *
     * @return Blesta\Items\Item\Item An Item instance
     */
    public function formatService(stdClass $fields)
    {
        return $this->make('invoiceLine', $fields);
    }
}
