<?php
/**
 *
 *
 */
class WhmcsConfiguration
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    public function get($setting=null)
    {
        $this->remote->select()->from('tblconfiguration');

        if ($setting) {
            $this->remote->where('setting', '=', $setting);
        }

        return $this->remote->getStatement();
    }
}
