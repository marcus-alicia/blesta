<?php
class AdminManagePlugin extends AppController
{
    /**
     * Performs necessary initialization
     */
    private function init()
    {
        // Require login
        $this->parent->requireLogin();

        $this->uses(['AutoCancel.AutoCancelSettings']);

        Language::loadLang(
            'admin_manage_plugin',
            null,
            PLUGINDIR . 'auto_cancel' . DS . 'language' . DS
        );

        // Set the page title
        $this->parent->structure->set(
            'page_title',
            Language::_(
                'AdminManagePlugin.'
                . Loader::fromCamelCase($this->action ? $this->action : 'index')
                . '.page_title',
                true
            )
        );

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'AutoCancel.default');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->init();

        $vars = (object) $this->AutoCancelSettings
            ->getSettings($this->parent->company_id);

        if (!empty($this->post)) {
            $this->AutoCancelSettings->setSettings(
                $this->parent->company_id,
                $this->post
            );

            if (($error = $this->AutoCancelSettings->errors())) {
                $this->parent->setMessage('error', $error);
            } else {
                $this->parent->setMessage(
                    'message',
                    Language::_('AdminManagePlugin.!success.settings_saved', true)
                );
            }

            $vars = (object) $this->post;
        }

        $days = $this->getDays(0, 60);
        // Set the view to render
        return $this->partial(
            'admin_manage_plugin',
            compact('vars', 'days')
        );
    }

    /**
     * Fetch days
     *
     * @param int $min_days
     * @param int $max_days
     * @return array
     */
    private function getDays($min_days, $max_days)
    {
        $days = [
            '' => Language::_('AdminManagePlugin.getDays.never', true)
        ];
        for ($i = $min_days; $i <= $max_days; $i++) {
            $days[$i] = Language::_(
                'AdminManagePlugin.getDays.text_day'
                . (
                    $i === 1
                    ? ''
                    : 's'
                ),
                true,
                $i
            );
        }
        return $days;
    }
}
