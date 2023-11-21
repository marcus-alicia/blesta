<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Invoice;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractItemFormatter;
use stdClass;

class InvoiceFormatter extends AbstractItemFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return Blesta\Items\Item\Item An Item instance
     */
    public function format(stdClass $fields)
    {
        return $this->make('invoiceData', $fields);
    }

    /**
     * {@inheritdoc}
     *
     * @return Blesta\Items\Item\Item An Item instance
     */
    public function formatService(stdClass $fields)
    {
        return $this->make('invoice', $fields);
    }
}
