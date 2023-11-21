<?php
namespace Blesta\Core\ServiceProviders;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Blesta\Core\Pricing\Presenter\PresenterFactory;
use Blesta\Core\Pricing\Presenter\Build\BuilderFactory;
use Blesta\Core\Pricing\Presenter\Items\ServiceFactory;
use Blesta\Core\Pricing\Presenter\Format\FormatFactory;
use Blesta\Core\Pricing\Presenter\Format\Fields\FormatFields;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\ItemFactory;

/**
 * Pricing service provider
 *
 * @package blesta
 * @subpackage blesta.core.ServiceProviders
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Pricing implements ServiceProviderInterface
{
    /**
     * @var Pimple\Container An instance of the container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function register(Container $container)
    {
        $this->container = $container;
        $this->registerPricing();

        // Add each factory/dependency as a container item
        $this->container->set('pricing', function ($c) {
            return new PricingFactory($c['pricing.options']);
        });

        $this->container->set('pricingPresenter', function ($c) {
            return new PresenterFactory();
        });

        $this->container->set('pricingPresenterService', function ($c) {
            return new ServiceFactory();
        });

        $this->container->set('items', function ($c) {
            return new ItemFactory();
        });

        $this->container->set('pricingPresenterFormatFields', function ($c) {
            return new FormatFields();
        });

        $this->container->set('pricingPresenterFormat', function ($c) {
            return new FormatFactory($c['items'], $c['pricingPresenterFormatFields']);
        });

        // Create the pricing builder factory to the container, composed of other container items
        $this->container['pricingBuilder'] = $this->container->factory(function ($c) {
            return new BuilderFactory(
                $c['pricingPresenterService'],
                $c['pricingPresenterFormat'],
                $c['pricing'],
                $c['pricingPresenter'],
                $c['items']
            );
        });
    }

    /**
     * Registers the PricingFactory default options. They can be overwritten by setting
     * another value in the container at runtime
     */
    private function registerPricing()
    {
        $this->container->set('pricing.options', function ($c) {
            return [
                'dateFormat' => 'M d, Y',
                'dateTimeFormat' => 'M d, Y g:i:s A'
            ];
        });
    }
}
