<?php
/**
 * Import Manager plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ImportManagerPlugin extends Plugin
{
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang('import_manager_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load components required by this plugin
        Loader::loadComponents($this, ['Input', 'Record']);
    }
}
