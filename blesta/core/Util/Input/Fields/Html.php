<?php
namespace Blesta\Core\Util\Input\Fields;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Input\Fields\InputField;
use \Loader;
use \View;

/**
 * Input Field Html
 *
 * Generates HTML for a given set of InputField objects, which may consist of
 * a label, tooltip, input field, or some combination thereof.
 *
 * @package blesta
 * @subpackage blesta.core.Util.Input.Fields
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @see InputField
 * @see InputFields
 */
class Html
{
    private $container_selector = '.generated_fields_div';

    /**
     * @var InputFields The fields for which to generate HTML
     */
    private $fields;

    public function __construct(InputFields $fields = null)
    {
        if ($fields === null) {
            $this->fields = new InputFields();
        } else {
            $this->setFields($fields);
        }

        Loader::loadHelpers($this, ['Form', 'Html']);

        // Initialize the main view
        $this->view = new View('partial_fields');
        Loader::loadHelpers($this->view, ['Form', 'Html']);
    }

    /**
     * Sets the InputFields that should be used for generating HTML
     *
     * @param InputFields $fields The fields for which to generate HTML
     */
    public function setFields(InputFields $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Gets the HTML for displaying the currently attached configurable options
     *
     * @param string $file The file to use for displaying configurable options
     * @param string $view_dir The directory in which to look for the configurable option display file
     * @return string The configurable option HTML
     */
    public function generate($file = null, $view_dir = null)
    {
        $this->view->set('fields', $this->fields->getFields());
        $this->view->set('html', $this->fields->getHtml());
        if ($file !== null) {
            $this->view->setView($file, null);
        }

        if ($view_dir !== null) {
            $this->view->setView(null, $view_dir);
        }

        return $this->view->fetch();
    }

    /**
     * Gets the selector for the element containing the configurable options
     *
     * @return string The element selector
     */
    public function getContainerSelector()
    {
        return $this->container_selector;
    }

    /**
     * Sets the selector for the element containing the configurable options
     *
     * @param string $container_selector The element selector
     */
    public function setContainerSelector($container_selector)
    {
        $this->container_selector = $container_selector;
    }
}