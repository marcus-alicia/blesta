<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description;

use Blesta\Core\Pricing\Modifier\Type\Description\Type\Discount\Discount;
use Blesta\Core\Pricing\Modifier\Type\Description\Type\Tax\Tax;
use Blesta\Core\Pricing\Modifier\Type\Description\Type\Service\Service;
use Blesta\Core\Pricing\Modifier\Type\Description\Type\Domain\Domain;
use Blesta\Core\Pricing\Modifier\Type\Description\Type\Option\Option;
use Blesta\Items\Collection\ItemCollection;
use Minphp\Date\Date;

/**
 * Description factory creates new instances of description items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DescriptionFactory
{
    /**
     * @var Minphp\Date\Date An instance of the date object
     */
    private $date;

    /**
     * @var array An array of options used to construct the presenter
     */
    private $options;

    /**
     * Init
     *
     * @param Minphp\Date\Date An instance of the Date object
     * @param array An array of options used to construct the presenter
     */
    public function __construct(Date $date, array $options = [])
    {
        $this->date = $date;
        $this->options = $options;
    }

    /**
     * Retrieves an instance of the Service description
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\Type\Service\Service
     */
    public function service()
    {
        return new Service($this->date, $this->options);
    }

    /**
     * Retrieves an instance of the Domain description
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\Type\Domain\Domain
     */
    public function domain()
    {
        return new Domain($this->date, $this->options);
    }

    /**
     * Retrieves an instance of the Service description
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\Type\Option\Option
     */
    public function option()
    {
        return new Option($this->date, $this->options);
    }

    /**
     * Retrieves an instance of the Discount description
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\Type\Discount\Discount
     */
    public function discount()
    {
        return new Discount($this->date, $this->options);
    }

    /**
     * Retrieves an instance of the Tax description
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\Type\Tax\Tax
     */
    public function tax()
    {
        return new Tax($this->date, $this->options);
    }
}
