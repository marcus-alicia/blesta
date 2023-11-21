<?php
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;

/**
 * Admin Company Messenger Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyMessengers extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['MessengerManager', 'Navigation']);

        Language::loadLang('admin_company_messengers');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Messenger settings page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/messengers/installed/');
    }

    /**
     * Messengers Installed page
     */
    public function installed()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('messengers', $this->MessengerManager->getAll($this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Messengers Available page
     */
    public function available()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('messengers', $this->MessengerManager->getAvailable($this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Sets the installed/available tabs to the view
     */
    private function setTabs()
    {
        $this->set(
            'link_tabs',
            [
                [
                    'name' => Language::_('AdminCompanyMessengers.!tab.installed', true),
                    'uri' => 'installed'
                ],
                [
                    'name' => Language::_('AdminCompanyMessengers.!tab.available', true),
                    'uri' => 'available'
                ]
            ]
        );
    }

    /**
     * Manage a module, displays the manage interface for the requested messenger
     * and processes any necessary messenger meta data
     */
    public function manage()
    {
        $messenger_id = isset($this->get[0]) ? $this->get[0] : null;

        // Redirect if the module ID is invalid
        if (!($messenger_info = $this->MessengerManager->get($messenger_id))
            || ($messenger_info->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/messenger/installed/');
        }

        $this->components(['Messengers']);

        $messenger = $this->Messengers->create($messenger_info->dir);
        $messenger->base_uri = $this->base_uri;

        // Get messenger meta
        $vars = $this->post;
        $meta = $this->MessengerManager->getMeta($messenger_id);

        if (empty($vars)) {
            $vars = (array) $meta;
        }

        // Get messenger configuration fields
        $fields = $messenger->getConfigurationFields($vars);

        if (!empty($this->post)) {
            if (($errors = $messenger->errors())) {
                $this->setMessage('error', $errors);
            } else {
                // Update the messenger meta data
                $this->MessengerManager->setMeta($messenger_id, $vars);
                if (($errors = $this->MessengerManager->errors())) {
                    $this->setMessage('error', $errors);
                } else {
                    $this->setMessage('success', Language::_('AdminCompanyMessengers.!success.messenger_updated', true));
                }
            }
        }

        $this->set('show_left_nav', !$this->isAjax());
        $this->set('fields', $fields->getFields());
        $this->set('input_html', (new FieldsHtml($fields)));
        $this->set('messenger_info', $messenger_info);
        $this->set('vars', $vars);
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyMessengers.manage.page_title', true, $messenger_info->name)
        );
    }

    /**
     * Install a messenger for this company
     */
    public function install()
    {
        if (!isset($this->post['id'])) {
            $this->redirect($this->base_uri . 'settings/company/messengers/available/');
        }

        $messenger_id = $this->MessengerManager->add(['dir' => $this->post['id'], 'company_id' => $this->company_id]);

        if (($errors = $this->MessengerManager->errors())) {
            $this->flashMessage('error', $errors);
            $this->redirect($this->base_uri . 'settings/company/messengers/available/');
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyMessengers.!success.installed', true));
            $this->redirect($this->base_uri . 'settings/company/messengers/installed/');
        }
    }

    /**
     * Uninstall a messenger for this company
     */
    public function uninstall()
    {
        if (!isset($this->post['id']) || !($messenger = $this->MessengerManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/messengers/installed/');
        }

        $this->MessengerManager->delete($this->post['id']);

        if (($errors = $this->MessengerManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyMessengers.!success.uninstalled', true));
        }
        $this->redirect($this->base_uri . 'settings/company/messengers/installed/');
    }

    /**
     * Upgrade a messenger
     */
    public function upgrade()
    {
        // Fetch the messenger to upgrade
        if (!isset($this->post['id']) || !($messenger = $this->MessengerManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/messengers/installed/');
        }

        $this->MessengerManager->upgrade($this->post['id']);

        if (($errors = $this->MessengerManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyMessengers.!success.upgraded', true));
        }
        $this->redirect($this->base_uri . 'settings/company/messengers/installed/');
    }

    /**
     * Messengers configuration
     */
    public function configuration()
    {
        $this->uses(['Messages']);

        // Get messenger types
        $types = $this->Messages->getTypes();

        // Get messengers
        $messengers = [];
        $installed_messengers = $this->MessengerManager->getAll($this->company_id);

        foreach (array_keys($types) as $type) {
            foreach ($installed_messengers as $messenger) {
                if (in_array($type, $messenger->types)) {
                    $messengers[$type][$messenger->id] = $messenger->name;
                }
            }
        }

        // Get messenger configuration
        $messenger_configuration = $this->Companies->getSetting($this->company_id, 'messenger_configuration');
        $messenger_configuration = isset($messenger_configuration->value) ? $messenger_configuration->value : '';

        $vars = unserialize(base64_decode($messenger_configuration));

        // Save messenger configuration
        if (!empty($this->post)) {
            $messenger_configuration = base64_encode(serialize($this->post['messenger_configuration']));

            $this->Companies->setSetting($this->company_id, 'messenger_configuration', $messenger_configuration);

            $this->flashMessage(
                'message',
                Language::_('AdminCompanyMessengers.!success.messenger_options_updated', true)
            );
            $this->redirect($this->base_uri . 'settings/company/messengers/configuration/');
        }

        $this->set('show_left_nav', !$this->isAjax());
        $this->set('messengers', $messengers);
        $this->set('types', $types);
        $this->set('vars', $vars);
    }

    /**
     * Messenger Templates
     */
    public function templates()
    {
        $this->uses(['MessageGroups']);

        // Get groups
        $groups = [
            'client' => $this->MessageGroups->getAllMessages($this->company_id),
            'staff' => $this->MessageGroups->getAllMessages($this->company_id, 'staff'),
            'plugins' => $this->MessageGroups->getAllMessages($this->company_id, null, false)
        ];

        // Set language for each group
        foreach ($groups as $type => &$group_list) {
            foreach ($group_list as &$group) {
                // Set plugin-specific language
                if ($type == 'plugins') {
                    Language::loadLang(
                        'admin_company_messengers',
                        null,
                        PLUGINDIR . $group->plugin_dir . DS . 'language' . DS
                    );
                }

                $group->group_name = Language::_(
                    'AdminCompanyMessengers.templates.' . $group->message_group_action . '_name',
                    true
                );
                $group->group_desc = Language::_(
                    'AdminCompanyMessengers.templates.' . $group->message_group_action . '_desc',
                    true
                );
            }
        }

        $this->set('groups', $groups);
        $this->set('show_left_nav', !$this->isAjax());
    }

    /**
     * Edit Message Template
     */
    public function editTemplate()
    {
        $this->uses(['Messages', 'Languages']);
        $this->helpers(['Form']);

        // Ensure a valid message group was given
        if (
            !isset($this->get[0])
            || !($template = $this->Messages->getByGroup($this->get[0], null, $this->company_id))
        ) {
            $this->redirect($this->base_uri . 'settings/company/messengers/templates/');
        }

        // Set message type
        $message_types = array_keys($template->messages);
        $message_type = isset($message_types[0]) ? $message_types[0] : null;

        if (isset($this->get[1]) && in_array($this->get[1], $message_types)) {
            $message_type = $this->get[1];
        }

        if (empty($message_type)) {
            $this->redirect($this->base_uri . 'settings/company/messengers/templates/');
        }

        // Update messenger template
        $vars = $template;
        if (!empty($this->post)) {
            foreach ($this->post['message'] as $message_id => $message) {
                // Set unchecked checkbox
                if (!isset($message['status'])) {
                    $message['status'] = 'inactive';
                }

                $content = [];
                foreach ($message['content'] as $message_lang => $message_content) {
                    $content[] = [
                        'lang' => $message_lang,
                        'content' => $message_content
                    ];
                }

                $vars = [
                    'message_group_id' => $template->id,
                    'status' => $message['status'],
                    'content' => $content
                ];

                $this->Messages->edit($message_id, $vars);
            }

            if (($errors = $this->Messages->errors())) {
                // Error
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyMessengers.!success.edit_template_updated', true)
                );
                $this->redirect($this->base_uri . 'settings/company/messengers/templates/');
            }
        }

        // Set the template name
        if (!empty($template->plugin_dir)) {
            Language::loadLang(
                'admin_company_messengers',
                null,
                PLUGINDIR . $template->plugin_dir . DS . 'language' . DS
            );
        }
        $template_name = Language::_('AdminCompanyMessengers.templates.' . $template->action . '_name', true);

        // Set template language names
        $languages = $this->Form->collapseObjectArray($this->Languages->getAll($this->company_id), 'name', 'code');

        foreach ($template->messages as $type => $message) {
            foreach ($message->content as $lang => $content) {
                if (array_key_exists($lang, $languages)) {
                    $template->messages[$type]->content[$lang]->lang_name = $languages[$lang];
                }
            }
        }

        $this->set('template_name', $template_name);
        $this->set('template', $template);
        $this->set('message_type', $message_type);
        $this->set('types', $this->Messages->getTypes());
        $this->set('vars', $vars);
    }
}
