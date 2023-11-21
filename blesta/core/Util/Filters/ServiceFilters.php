<?php
namespace Blesta\Core\Util\Filters;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Filters\Common\FiltersInterface;
use \Loader;
use \Language;

/**
 * Service Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering services
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - company_id The company ID to filter modules on
     *  - client True to fetch the filters for the client interface, false to fetch the filters for the admin interface
     *  - client_id The client ID to filter modules on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - module_id The module ID on which to filter packages
     *  - package_name The (partial) name of the packages for which to fetch services
     *  - service_meta The (partial) value of meta data on which to filter services
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadComponents($this, ['Record']);
        Loader::loadModels($this, ['ModuleManager']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'service_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        if (!isset($options['client'])) {
            $options['client'] = false;
        }

        $fields = new InputFields();

        // Set module ID filter
        if (!$options['client']) {
            $modules = $this->Form->collapseObjectArray(
                $this->ModuleManager->getAll($options['company_id']),
                'name',
                'id'
            );

            if (isset($options['client_id'])) {
                $client_modules = $this->Record->select(['modules.id', 'modules.name'])
                    ->from('services')
                    ->where('services.client_id', '=', $options['client_id'])
                    ->innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false)
                    ->innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)
                    ->innerJoin('modules', 'modules.id', '=', 'packages.module_id', false)
                    ->group('modules.id')
                    ->fetchAll();

                $modules = $this->Form->collapseObjectArray($client_modules, 'name', 'id');
            }

            $module = $fields->label(
                Language::_('Util.filters.service_filters.field_module_id', true),
                'module_id'
            );
            $module->attach(
                $fields->fieldSelect(
                    'filters[module_id]',
                    ['' => Language::_('Util.filters.service_filters.any', true)] + $modules,
                    isset($vars['module_id']) ? $vars['module_id'] : null,
                    ['id' => 'module_id', 'class' => 'form-control']
                )
            );
            $fields->setField($module);
        }

        // Set the package name filter
        $package_name = $fields->label(
            Language::_('Util.filters.service_filters.field_package_name', true),
            'package_name'
        );
        $package_name->attach(
            $fields->fieldText(
                'filters[package_name]',
                isset($vars['package_name']) ? $vars['package_name'] : null,
                [
                    'id' => 'package_name',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.service_filters.field_package_name', true)
                ]
            )
        );
        $fields->setField($package_name);

        // Set the service meta filter
        $service_meta = $fields->label(
            Language::_('Util.filters.service_filters.field_service_meta', true),
            'service_meta'
        );
        $service_meta->attach(
            $fields->fieldText(
                'filters[service_meta]',
                isset($vars['service_meta']) ? $vars['service_meta'] : null,
                [
                    'id' => 'service_meta',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.service_filters.field_service_meta', true)
                ]
            )
        );
        $fields->setField($service_meta);

        return $fields;
    }
}
