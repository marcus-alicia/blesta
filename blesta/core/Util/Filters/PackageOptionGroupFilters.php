<?php
namespace Blesta\Core\Util\Filters;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Filters\Common\FiltersInterface;
use \Loader;
use \Language;

/**
 * Package Option Group Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageOptionGroupFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering packages
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - company_id The company ID to filter modules on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - hidden Whether or not to show the hidden package groups
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadModels($this, ['ModuleManager']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'package_option_group_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set the hidden filter
        $hidden_packages = $fields->label(
            Language::_('Util.filters.package_option_group_filters.field_options', true)
        );
        $hidden_packages->attach(
            $fields->fieldCheckbox(
                'filters[hidden]',
                'true',
                isset($vars['hidden']) && $vars['hidden'],
                ['id' => 'hidden'],
                $fields->label(
                    Language::_('Util.filters.package_option_group_filters.options.hidden', true),
                    'hidden'
                )
            )
        );
        $fields->setField($hidden_packages);

        return $fields;
    }
}
