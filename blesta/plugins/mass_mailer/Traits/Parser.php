<?php
namespace Blesta\MassMailer\Traits;

use Configure;
use H2o;
use Loader;

/**
 * MassMailer Parser trait
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.traits
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait Parser
{
    /**
     * Retrieves a set of default tags for company URIs
     * @see Emails model
     *
     * @return array An array of default tags
     */
    protected function getDefaultTags()
    {
        // Fetch the company
        $company = Configure::get('Blesta.company');
        $tags = [];

        if ($company) {
            $webdir = WEBDIR;

            // Set the URIs to the admin/client portals
            $tags['base_uri'] = $company->hostname . $webdir;
            $tags['admin_uri'] = $company->hostname . $webdir . Configure::get('Route.admin') . '/';
            $tags['client_uri'] = $company->hostname . $webdir . Configure::get('Route.client') . '/';
        }

        return $tags;
    }

    /**
     * Retrieves the H2o email parser
     *
     * @return H2o The email parser
     */
    protected function getParser()
    {
        // Load the template parser
        $parser = new H2o();
        $parser = $this->setFilters($parser);

        return $parser;
    }

    /**
     * Sets filters mimicing the Emails model
     * @see Emails model
     *
     * @param H2o $parser The parser object to set filters for
     * @return H2o $parser
     */
    private function setFilters(H2o $parser)
    {
        Loader::loadHelpers($this, ['CurrencyFormat']);

        $this->CurrencyFormat->setCompany(Configure::get('Blesta.company_id'));
        $parser->addFilter('currency_format', [$this->CurrencyFormat, 'format']);

        return $parser;
    }

    /**
     * Retrieves a set of H2o parser options
     * @see Emails model
     *
     * @return array An array of parser options containing:
     *  - html The HTML parser options
     *  - text The Text parser options
     */
    protected function getParserOptions()
    {
        $options = Configure::get('Blesta.parser_options');
        $options['autoescape'] = false;

        return [
            'html' => $options,
            'text' => $options
        ];
    }

    /**
     * Retrieves tag replacements for a specific contact
     *
     * @param int $contact_id The ID of the contact
     * @param int $service_id The ID of one of the contact's services
     * @return array An array of tag replacements
     */
    protected function getContactTags($contact_id, $service_id = null)
    {
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        $tags = [];
        if (($contact = $this->Contacts->get($contact_id))) {
            $tags = [
                'contact' => $contact,
                'client' => $this->Clients->get($contact->client_id, false)
            ];
        }

        // Add tags for service, module, package
        if ($service_id && ($service = $this->Services->get($service_id))) {
            $tags['service'] = $service;
            $tags['module'] = $this->getModuleTags($service->module_row_id);
            $tags['package'] = $this->getPackageTags($service->pricing_id);
        }

        return $tags;
    }

    /**
     * Retrieves the package tags for a service
     *
     * @param int $pricing_id The service's package pricing ID
     * @return stdClass An stdClass object representing the package
     */
    private function getPackageTags($pricing_id)
    {
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }

        $tags = (object)[];
        if (($package = $this->Packages->getByPricingId($pricing_id))) {
            // Add each package meta field as a tag
            if (!empty($package->meta)) {
                $fields = [];
                foreach ($package->meta as $key => $value) {
                    $fields[$key] = $value;
                }
                $package = (object)array_merge((array)$package, $fields);
            }

            $tags = $package;
        }

        return $tags;
    }

    /**
     * Retrieves the module tags for a module row
     *
     * @param int $module_row_id The ID of the module row
     * @return stdClass An stdClass object representing the module
     */
    private function getModuleTags($module_row_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $tags = (object)[];

        // Fetch the module row
        $module_row = $this->ModuleManager->getRow($module_row_id);
        if ($module_row) {
            // Fetch the module
            $module = $this->ModuleManager->initModule(
                $module_row->module_id,
                Configure::get('Blesta.company_id')
            );
        }

        // No module row or module, no tags
        if (empty($module_row) || empty($module)) {
            return $tags;
        }

        $tags->name = $module->getName();
        $module_fields = [];
        $row_key = $module->moduleRowMetaKey();

        // Set all acceptable module meta fields
        if (!empty($module_row->meta)) {
            $meta_tags = $module->getEmailTags();
            $meta_tags = (
                isset($meta_tags['module']) && is_array($meta_tags['module'])
                ? $meta_tags['module']
                : []
            );

            if (!empty($meta_tags)) {
                foreach ($module_row->meta as $key => $value) {
                    if (in_array($key, $meta_tags)) {
                        $module_fields[$key] = $value;
                    }

                    // Set the module row label
                    if ($key == $row_key) {
                        $tags->label = $value;
                    }
                }
            }
        }

        return (object)(array_merge($module_fields, (array)$tags));
    }
}
