<?php
namespace Blesta\Core\ServiceProviders;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Configure;
use Language;
use Loader;

/**
 * Pagination service provider
 *
 * @package blesta
 * @subpackage blesta.core.ServiceProviders
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Pagination implements ServiceProviderInterface
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

        $this->container['pagination'] = $this->container->factory(function ($c) {
            // Autoload the language file
            Language::loadLang(
                ['Pagination'],
                Configure::get('Blesta.language'),
                dirname(__FILE__) . DS . 'language' . DS
            );

            Loader::loadHelpers($this, ['Pagination']);
            $this->Pagination->setSettings([
                'navigation' => [
                    'next' => ['name' => Language::_('Pagination.next', true)],
                    'prev' => ['name' => Language::_('Pagination.prev', true)],
                    'first' => ['name' => Language::_('Pagination.first', true)],
                    'last' => ['name' => Language::_('Pagination.last', true)],
                ]
            ]);

            return $this->Pagination;
        });
    }
}
