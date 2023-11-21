<?php
Loader::load(dirname(__FILE__) . DS . 'invoice_template.php');
/**
 * Invoice Template factory
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceTemplates
{

    /**
     * Returns an instance of the requested invoice template
     *
     * @param string $template_name The name of the invoice template to instantiate
     * @return mixed An object of type $template_name
     * @throws Exception Thrown when the template_name does not exists or does not inherit from the appropriate parent
     */
    public static function create($template_name)
    {
        $template_name = Loader::toCamelCase($template_name);
        $template_file = Loader::fromCamelCase($template_name);

        if (!Loader::load(COMPONENTDIR . 'invoice_templates' . DS . $template_file . DS . $template_file . '.php')) {
            throw new Exception("Invoice Template '" . $template_name . "' does not exist.");
        }

        if (class_exists($template_name) && is_subclass_of($template_name, 'InvoiceTemplate')) {
            return new $template_name();
        }

        throw new Exception("Invoice Template '" . $template_name . "' is not a recognized invoice template.");
    }
}
