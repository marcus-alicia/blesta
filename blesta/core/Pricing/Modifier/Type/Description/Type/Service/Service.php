<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description\Type\Service;

use Blesta\Core\Pricing\Modifier\Type\Description\Type\AbstractDescription;

/**
 * Retrieves item descriptions for packages/services
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description.Type.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Service extends AbstractDescription
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
            case 'service':
                $description = $this->getService($meta, $oldMeta);
                break;
            case 'package':
                $description = $this->getPackage($meta, $oldMeta);
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
     * Generates a description from the service meta data for the service item
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getService(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);
        $oldFields = ($oldMeta ? $this->getBaseFields($oldMeta) : []);

        // Set the old meta service name if we do not have one ourselves
        if (empty($fields['service']) && !empty($oldFields['service'])) {
            $fields['service'] = $oldFields['service'];
        }

        // Include dates only if we have both, which we will if we are prorating
        $prorated = (!empty($fields['prorated']) && $fields['prorated']);
        $showDates = (!empty($fields['startDate']) && !empty($fields['endDate']));
        $term = 'Service.description.service.item';
        $languageValues = [];

        // If we are changing from one package to another, show that fact
        if (!empty($oldFields['package_id']) && $fields['package_id'] != $oldFields['package_id']) {
            $term .= '.updated';
            $languageValues[] = $oldFields['package'];
        }

        $languageValues[] = $fields['package'];
        $languageValues[] = $fields['service'];

        // If prorating, or we have dates to show, include them in the definition
        if ($prorated || $showDates) {
            // Update to a term that is prorating or includes dates
            if ($prorated) {
                $term .= '.prorate';
            } elseif ($showDates) {
                $term .= '.date';
            }

            $languageValues[] = $fields['startDate'];
            $languageValues[] = $fields['endDate'];
        }

        return call_user_func_array([$this, '_'], array_merge([$term], $languageValues));
    }

    /**
     * Generates a description from the service meta data for the package
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getPackage(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);
        $oldFields = ($oldMeta ? $this->getBaseFields($oldMeta) : []);

        // Use the service description language definition if we have that info
        if (!empty($fields['service']) || (!empty($oldFields) && !empty($oldFields['service']))) {
            return $this->getService($meta, $oldMeta);
        }

        // Include dates only if we have both, which we will if we are prorating
        $prorated = (!empty($fields['prorated']) && $fields['prorated']);
        $showDates = (!empty($fields['startDate']) && !empty($fields['endDate']));
        $term = 'Service.description.package.item';
        $languageValues = [];

        // If we are changing from one package to another, show that fact
        if (!empty($oldFields['package_id']) && $fields['package_id'] != $oldFields['package_id']) {
            $term .= '.updated';
            $languageValues[] = $oldFields['package'];
        }

        $languageValues[] = $fields['package'];

        // If prorating, or we have dates to show, include them in the definition
        if ($prorated || $showDates) {
            // Update to a term that is prorating or includes dates
            if ($prorated) {
                $term .= '.prorate';
            } elseif ($showDates) {
                $term .= '.date';
            }

            $languageValues[] = $fields['startDate'];
            $languageValues[] = $fields['endDate'];
        }

        return call_user_func_array([$this, '_'], array_merge([$term], $languageValues));
    }

    /**
     * Generates a description from the service meta data for the setup fee
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getSetup(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);
        $oldFields = ($oldMeta ? $this->getBaseFields($oldMeta) : []);

        // Set the term based on the info we have.
        // We may not have a service name if the item is just service data
        $term = 'Service.description.service.setup';
        if (empty($fields['service'])) {
            // Use the old meta service name if available
            if (empty($oldFields['service'])) {
                $term = 'Service.description.package.setup';
            } else {
                $fields['service'] = $oldFields['service'];
            }
        }

        return $this->_($term, $fields['package'], $fields['service']);
    }

    /**
     * Generates a description from the service meta data for the cancel fee
     *
     * @param array $meta An array of meta information
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    private function getCancel(array $meta, array $oldMeta = null)
    {
        $fields = $this->getBaseFields($meta);
        $oldFields = ($oldMeta ? $this->getBaseFields($oldMeta) : []);

        // Set the term based on the info we have.
        // We may not have a service name if the item is just service data
        $term = 'Service.description.service.cancel';
        if (empty($fields['service'])) {
            // Use the old meta service name if available
            if (empty($oldFields['service'])) {
                $term = 'Service.description.package.cancel';
            } else {
                $fields['service'] = $oldFields['service'];
            }
        }

        return $this->_($term, $fields['package'], $fields['service']);
    }

    /**
     * Retrieves a set of meta fields used in the descriptions
     *
     * @param array $meta An array of meta information
     * @return array An array containing the service and package name
     */
    private function getBaseFields(array $meta)
    {
        $service = (isset($meta['service']) && is_object($meta['service']) && isset($meta['service']->name))
            ? $meta['service']->name
            : '';
        $package = (isset($meta['package']) && is_object($meta['package']) && isset($meta['package']->name))
            ? $meta['package']->name
            : '';
        $package_id = (isset($meta['package']) && is_object($meta['package']) && isset($meta['package']->id))
            ? $meta['package']->id
            : '';

        $state = null;
        $startDate = null;
        $endDate = null;

        if (isset($meta['_data']) && is_array($meta['_data'])) {
            $state = isset($meta['_data']['state']) ? $meta['_data']['state'] : '';
            $startDate = isset($meta['_data']['startDate'])
                ? $this->date->cast($meta['_data']['startDate'], 'date')
                : null;
            $endDate = isset($meta['_data']['endDate'])
                ? $this->date->cast($meta['_data']['endDate'], 'date')
                : null;
        }

        return array_merge(
            compact('service', 'package', 'package_id', 'state', 'startDate', 'endDate'),
            $this->getProrateFields($meta)
        );
    }
}
