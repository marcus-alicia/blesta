<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description\Type\Option;

use Blesta\Core\Pricing\Modifier\Type\Description\Type\AbstractDescription;

/**
 * Retrieves item descriptions for package/service options
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description.Type.Option
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Option extends AbstractDescription
{
    /**
     * {@inheritdoc}
     */
    public function get(array $meta, array $oldMeta = null)
    {
        $description = '';

        // Determine the type of meta info we have to work with
        $type = (isset($meta['_data']['type']) ? $meta['_data']['type'] : null);
        switch ($type) {
            case 'option':
                $description = $this->getOption($meta, $oldMeta);
                break;
            case 'setup':
                $description = $this->getSetup($meta, $oldMeta);
                break;
            case 'cancel':
                $description = $this->getCancel($meta, $oldMeta);
                break;
        }

        return $description;
    }

    /**
     * Generates a description from the option meta data for the option item
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getOption(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);
        $state = $fields['state'];

        // Build the language definition from the option data
        $prorated = (!empty($fields['prorated']) && $fields['prorated']);
        $showDates = (!empty($fields['showDates']) && $fields['showDates']
            && !empty($fields['startDate']) && !empty($fields['endDate'])
        );
        $qty = ($fields['type'] == 'quantity');
        $term = 'Option.description.option.item'
            . ($qty ? '_qty' : '')
            . ($state && $state != 'updated' ? '.' . $state : '')
            . ($prorated ? '.prorated' : '')
            . ($showDates ? '.date' : '');

        // Options of the text/textarea/password type do not include values in the description
        if (in_array($fields['type'], ['text', 'password', 'textarea'])) {
            $term .= '.text';
            $languageValues = array_merge(
                [$term, $fields['option']],
                ($showDates ? [$fields['startDate'], $fields['endDate']] : [])
            );
        } else {
            $languageValues = array_merge(
                [$term, $fields['option'], $fields['value']],
                ($qty ? [$fields['qty']] : []),
                ($showDates ? [$fields['startDate'], $fields['endDate']] : [])
            );
        }

        $definition = call_user_func_array([$this, '_'], $languageValues);

        // If the state is 'updated', we need to use the old meta data
        // to pass additional info to the definition
        if ($state == 'updated') {
            $updatedDefinition = $this->getOptionUpdated($fields, ($oldMeta ? $this->getBaseFields($oldMeta) : []));
            $definition = (empty($updatedDefinition) ? $definition : $updatedDefinition);
        }

        return $this->description($definition);
    }

    /**
     * Generates a description from the option meta data for the option item
     *
     * @param array $fields An array of meta information
     * @param array $oldFields An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getOptionUpdated(array $fields, array $oldFields)
    {
        // Build the language definition from the option data
        $prorated = (!empty($fields['prorated']) && $fields['prorated']);
        $showDates = (!empty($fields['showDates']) && $fields['showDates']
            && !empty($fields['startDate']) && !empty($fields['endDate'])
        );
        $qty = ($fields['type'] == 'quantity');
        $term = 'Option.description.option.item' . ($qty ? '_qty' : '') . '.updated'
            . ($prorated ? '.prorated' : '')
            . ($showDates ? '.date' : '');

        // Options of the text/textarea/password type do not include values in the description
        if (in_array($fields['type'], ['text', 'password', 'textarea'])) {
            $term .= '.text';
            $languageValues = array_merge(
                [$term, $fields['option']],
                ($showDates ? [$fields['startDate'], $fields['endDate']] : [])
            );
        } else {
            $languageValues = array_merge(
                [$term, $fields['option'], $fields['value']],
                ($qty
                    ? [$fields['qty'], (isset($oldFields['qty']) ? $oldFields['qty'] : '')]
                    : [(isset($oldFields['value']) ? $oldFields['value'] : '')]
                ),
                ($showDates ? [$fields['startDate'], $fields['endDate']] : [])
            );
        }

        return call_user_func_array([$this, '_'], $languageValues);
    }

    /**
     * Generates a description from the option meta data for the setup fee
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getSetup(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);

        return $this->description($this->_('Option.description.option.setup', $fields['option'], $fields['value']));
    }

    /**
     * Generates a description from the option meta data for the cancel fee
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getCancel(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);

        return $this->description($this->_('Option.description.option.cancel', $fields['option'], $fields['value']));
    }

    /**
     * Applies a prefix to the given definition
     *
     * @param string $definition The definition to set a prefix for
     * @return string The given $definition with a prefix
     */
    private function description($definition)
    {
        // Set a prefix since option descriptions appear below their services
        return $this->_('Option.description.prefix.nest') . ' ' . $definition;
    }

    /**
     * Retrieves a set of meta fields used in the descriptions
     *
     * @param array $meta An array of meta information
     * @return array An array containing the package option name, value, and quantity
     */
    private function getBaseFields(array $meta)
    {
        $option = '';
        $value = '';
        $qty = '';
        $type = '';
        $state = '';
        $startDate = null;
        $endDate = null;
        $showDates = false;

        if (isset($meta['_data']) && is_array($meta['_data'])) {
            $state = (isset($meta['_data']['state'])
                && in_array($meta['_data']['state'], ['added', 'updated', 'removed']))
                ? $meta['_data']['state']
                : '';
            $startDate = isset($meta['_data']['startDate'])
                ? $this->date->cast($meta['_data']['startDate'], 'date')
                : null;
            $endDate = isset($meta['_data']['endDate'])
                ? $this->date->cast($meta['_data']['endDate'], 'date')
                : null;
            $showDates = isset($meta['_data']['show_dates']) && $meta['_data']['show_dates'];
        }

        if (isset($meta['option']) && is_object($meta['option'])) {
            $option = isset($meta['option']->label) ? $meta['option']->label : '';
            $value = isset($meta['option']->value_name) ? $meta['option']->value_name : '';
            $qty = isset($meta['option']->qty) ? $meta['option']->qty : '';
            $type = isset($meta['option']->type) ? $meta['option']->type : '';
        }

        return array_merge(
            compact('option', 'value', 'qty', 'type', 'state', 'startDate', 'endDate', 'showDates'),
            $this->getProrateFields($meta)
        );
    }
}
