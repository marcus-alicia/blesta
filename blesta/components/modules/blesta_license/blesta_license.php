<?php

use Blesta\Module\BlestaLicense\Module;

/**
 * Blesta License Module
 *
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BlestaLicense extends Module
{

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, array('Input'));

        // Load helpers required by this module
        Loader::loadHelpers($this, array('Html'));

        // Load the language required by this module
        Language::loadLang('license_module', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * {@inheritdoc}
     */
    public function manageModule($module, array &$vars)
    {
        $view = $this->getView('manage');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('module', $module);
        $view->set('vars', (object)$vars);

        return $view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function manageAddRow(array &$vars)
    {
        $view = $this->getView('add_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('vars', (object)$vars);
        return $view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function manageEditRow($module_row, array &$vars)
    {
        $view = $this->getView('edit_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $view->set('vars', (object)$vars);
        return $view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function addModuleRow(array &$vars)
    {
        $meta = array();
        foreach ($vars as $key => $value) {
            $meta[] = array(
                'key' => $key,
                'value' => $value,
                'encrypted' => 1
            );
        }
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function editModuleRow($module_row, array &$vars)
    {
        return $this->addModuleRow($vars);
    }

    /**
     * Load the view
     *
     * @param string $view
     * @return \View
     */
    protected function getView($view)
    {
        $viewObj = new View($view, 'default');
        $viewObj->base_uri = $this->base_uri;
        $viewObj->setDefaultView(
            'components' . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'blesta_license' . DIRECTORY_SEPARATOR
            . 'src' . DIRECTORY_SEPARATOR
        );

        return $viewObj;
    }
}
