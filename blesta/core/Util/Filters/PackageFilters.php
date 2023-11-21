<?php
namespace Blesta\Core\Util\Filters;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Filters\Common\FiltersInterface;
use \Loader;
use \Language;

/**
 * Package Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering packages
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - company_id The company ID to filter modules on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - name The name or part of the name of the packages to fetch
     *  - module_id The module ID to filter packages on
     *  - assigned_services The service status on which to filter packages
     *  - hidden Whether or not to show the hidden packages
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadModels($this, ['ModuleManager', 'PackageGroups']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'package_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set module ID filter
        $modules = $this->Form->collapseObjectArray($this->ModuleManager->getAll($options['company_id']), 'name', 'id');
        $module = $fields->label(
            Language::_('Util.filters.package_filters.field_module_id', true),
            'module_id'
        );
        $module->attach(
            $fields->fieldSelect(
                'filters[module_id]',
                ['' => Language::_('Util.filters.package_filters.any', true)] + $modules,
                isset($vars['module_id']) ? $vars['module_id'] : null,
                ['id' => 'module_id', 'class' => 'form-control']
            )
        );
        $fields->setField($module);
        
        // Set package group ID filter
        $package_groups = $this->Form->collapseObjectArray($this->PackageGroups->getAll($options['company_id']), 'name', 'id');
        $package_group = $fields->label(
            Language::_('Util.filters.package_filters.field_package_group_id', true),
            'package_group_id'
        );
        $package_group->attach(
            $fields->fieldSelect(
                'filters[package_group_id]',
                ['' => Language::_('Util.filters.package_filters.any', true)] + $package_groups,
                $vars['package_group_id'] ?? null,
                ['id' => 'package_group_id', 'class' => 'form-control']
            )
        );
        $fields->setField($package_group);

        // Set the package name filter
        $name = $fields->label(Language::_('Util.filters.package_filters.field_name', true), 'name');
        $name->attach(
            $fields->fieldText(
                'filters[name]',
                isset($vars['name']) ? $vars['name'] : null,
                [
                    'id' => 'name',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.package_filters.field_name', true)
                ]
            )
        );
        $fields->setField($name);

        // Set the assigned services filter
        $assigned_services = $fields->label(
            Language::_('Util.filters.package_filters.field_assigned_services', true),
            'assigned_services'
        );
        $assigned_service_options = [
            '' => Language::_('Util.filters.package_filters.services.na', true),
            'any' => Language::_('Util.filters.package_filters.services.any', true),
            'canceled' => Language::_('Util.filters.package_filters.services.canceled', true),
            'none' => Language::_('Util.filters.package_filters.services.none', true)
        ];
        $assigned_services->attach(
            $fields->fieldSelect(
                'filters[assigned_services]',
                $assigned_service_options,
                isset($vars['assigned_services']) ? $vars['assigned_services'] : null,
                ['id' => 'assigned_services', 'class' => 'form-control']
            )
        );
        $fields->setField($assigned_services);

        // Set the hidden filter
        $hidden_packages = $fields->label(
            Language::_('Util.filters.package_filters.field_options', true)
        );
        $hidden_packages->attach(
            $fields->fieldCheckbox(
                'filters[hidden]',
                '1',
                ($vars['hidden'] ?? '') == '1',
                ['id' => 'hidden'],
                $fields->label(
                    Language::_('Util.filters.package_filters.options.hidden', true),
                    'hidden'
                )
            )
        );
        $fields->setField($hidden_packages);

        return $fields;
    }
}
