<?php
/**
 * Support Manager parent model
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerModel extends AppModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Configure::load('support_manager', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Retrieves the web directory
     *
     * @return string The web directory
     */
    public function getWebDirectory()
    {
        $webdir = WEBDIR;
        $is_cli = (empty($_SERVER['REQUEST_URI']));

        // Set default webdir if running via CLI
        if ($is_cli) {
            Loader::loadModels($this, ['Settings']);
            $root_web = $this->Settings->getSetting('root_web_dir');
            if ($root_web) {
                $webdir = str_replace(DS, '/', str_replace(rtrim($root_web->value, DS), '', ROOTWEBDIR));

                if (!HTACCESS) {
                    $webdir .= 'index.php/';
                }
            }
        }

        return $webdir;
    }
}
