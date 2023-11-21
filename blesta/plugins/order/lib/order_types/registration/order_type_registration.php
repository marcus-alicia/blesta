<?php
/**
 * Client Registration Order Type
 *
 * @package blesta
 * @subpackage blesta.plugins.order.lib.order_types
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderTypeRegistration extends OrderType
{
    /**
     * @var string The authors of this order type
     */
    private static $authors = [['name'=>'Phillips Data, Inc.','url'=>'http://www.blesta.com']];

    /**
     * Construct
     */
    public function __construct()
    {
        Language::loadLang('order_type_registration', null, dirname(__FILE__) . DS . 'language' . DS);

        Loader::loadComponents($this, ['Input']);
    }

    /**
     * Returns the name of this order type
     *
     * @return string The common name of this order type
     */
    public function getName()
    {
        return Language::_('OrderTypeRegistration.name', true);
    }

    /**
     * Returns the name and URL for the authors of this order type
     *
     * @return array The name and URL of the authors of this order type
     */
    public function getAuthors()
    {
        return self::$authors;
    }

    /**
     * Validates the given data (settings) to be updated for this order form
     *
     * @param array $vars An array of order form data (including meta data unique to this form type)
     *  to be updated for this order form
     * @return array The order form data to be updated in the database for this order form,
     *  or reset into the form on failure
     */
    public function editSettings(array $vars)
    {
        $rules = [
            'template' => [
                'valid' => [
                    'rule' => ['compares', '==', 'standard'],
                    'message' => Language::_('OrderTypeRegistration.!error.template.valid', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            return $vars;
        }
    }

    /**
     * Determines whether or not the order type requires the perConfig step of
     * the order process to be invoked.
     *
     * @return bool If true will invoke the preConfig step before selecting a package,
     *  false to continue to the next step
     */
    public function requiresPreConfig()
    {
        return true;
    }

    /**
     * Handle an HTTP request. This allows an order template to execute custom code
     * for the order type being used, allowing tighter integration between the order type and the template.
     * This can be useful for supporting AJAX requests and the like.
     *
     * @param array $get All GET request parameters
     * @param array $post All POST request parameters
     * @param array $files All FILES request parameters
     * @return string HTML content to render (if any)
     */
    public function handleRequest(array $get = null, array $post = null, array $files = null)
    {
        Loader::loadComponents($this, ['Session']);
        // If already logged in, redirect away and display success message
        if ($this->Session->read('blesta_client_id')) {
            $this->Session->write(
                'flash',
                array_merge(
                    ['message' => Language::_('OrderTypeRegistration.!success.signup', true)],
                    [],
                    ['in_current_view' => false]
                )
            );
            header('Location: ' . $get['base_uri']);
            exit;
        }
    }

    /**
     * Determines whether or not the order type supports multiple package groups or just a single package group
     *
     * @return mixed If true will allow multiple package groups to be selected,
     *  false allows just a single package group, null will not allow package selection
     */
    public function supportsMultipleGroups()
    {
        return null;
    }

    /**
     * Determines whether or not the order type supports accepting payments
     *
     * @return bool If true will allow currencies and gateways to be selected for the order type
     */
    public function supportsPayments()
    {
        return false;
    }
}
