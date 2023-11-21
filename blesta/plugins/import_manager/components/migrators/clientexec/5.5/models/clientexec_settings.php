<?php
/**
 * Generic Clientexec Settings Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecSettings
{
    /**
     * ClientexecSettings constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all settings.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        $settings = $this->remote->select()->from('setting')->getStatement()->fetchAll();

        foreach ($settings as $key => $value) {
            $settings[$key]->name = strtolower(str_replace(' ', '_', $value->name));
        }

        return $settings;
    }

    /**
     * Get an specific setting.
     *
     * @param mixed $key
     * @return mixed The result of the sql transaction
     */
    public function getSetting($key)
    {
        $settings = $this->get();

        foreach ($settings as $setting) {
            if ($setting->name == $key) {
                return $setting;
            }
        }

        return null;
    }
}
