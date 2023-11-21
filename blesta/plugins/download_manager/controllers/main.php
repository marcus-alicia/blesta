<?php
/**
 * Download Manager Client Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends DownloadManagerController
{
    public function preAction()
    {
        $this->redirect($this->base_uri . 'plugin/download_manager/client_main/');
    }
}
