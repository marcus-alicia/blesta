<?php
/**
 * Mass Mailer Admin Compose controller
 * Step 2&3 of composing a mass email
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.controllers
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompose extends MassMailerController
{
    /**
     * @var array Accepted email options
     */
    private $email_fields = [
        'from_name', 'from_address', 'subject', 'html', 'text', 'log'
    ];

    /**
     * Setup page
     */
    public function preAction()
    {
        parent::preAction();

        $this->structure->set('page_title', Language::_('AdminCompose.index.page_title', true));

        // Filters from the first step must be defined
        $session = $this->read();
        if (!is_array($session) || !array_key_exists('filters', $session)) {
            $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_main/');
        }

        $this->uses(['MassMailer.MassMailerEmails']);

        Language::loadLang('tags', null, PLUGINDIR . 'mass_mailer' . DS . 'language' . DS);
    }

    /**
     * List mailings
     */
    public function index()
    {
        if (!empty($this->post)) {
            // Set checkboxes
            if (!isset($this->post['log'])) {
                $this->post['log'] = '0';
            }

            // Only set the expected fields
            $data = array_intersect_key($this->post, array_flip($this->email_fields));

            // Email content valid?
            $this->MassMailerEmails->validate($data);
            if (($errors = $this->MassMailerEmails->errors())) {
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                // Email passed validation, preview the email
                $this->write('email', $data);
                $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_compose/preview/');
            }
        }

        // Set the email information from the session if available
        if (!isset($vars)) {
            $session = $this->read();
            if (is_array($session) && array_key_exists('email', $session)) {
                $vars = (object)$session['email'];
            }
        }

        $this->set('vars', (isset($vars) ? $vars : $this->getDefaultVars()));
        $this->set('tags', $this->getTags());
        $this->setWysiwyg();
    }

    /**
     * Preview the email
     */
    public function preview()
    {
        // The email from the previous step must be defined
        $session = $this->read();
        if (!is_array($session) || !array_key_exists('email', $session)) {
            $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_main/');
        }

        // Create the mailing job
        if (!empty($this->post)) {
            $this->addJob();
        }

        $this->uses(['MassMailer.MassMailerClients']);

        // Fetch the total number of email recipients
        $total = $this->MassMailerClients->getAllCount($session['filters']);

        // Generate a sample email using one of the recipients
        $sample = (object)[];
        if ($total > 0) {
            $contacts = $this->MassMailerClients->getAll($session['filters']);
            foreach ($contacts as $contact) {
                break;
            }

            // Attempt to generate a sample email for review
            try {
                $sample = (object)array_merge(
                    (array)$session['email'],
                    (array)$this->MassMailerEmails->getSample($session['email'], $contact)
                );
            } catch (H2o_Error $e) {
                // Parse error, redirect back to edit the email template again
                $this->flashMessage(
                    'error',
                    Language::_('AdminCompose.!error.parse_error', true, $e->getMessage()),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_compose/');
            }

            // Write the HTML sample to the session to be fetched by the iframe
            $this->write('sample_html', $sample->html);
        }

        $this->set(compact('total', 'sample'));
    }

    /**
     * Sample HTML email page
     */
    public function sample()
    {
        $html = '';
        $session = $this->read();

        if (isset($session['sample_html'])) {
            $html = $session['sample_html'];
        }

        echo $this->partial('partials' . DS . 'sample_email', compact('html'));
        exit();
    }

    /**
     * Returns default values for composing an email
     *
     * @return stdClass An stdClass object representing input fields with default values set
     */
    protected function getDefaultVars()
    {
        $vars = new stdClass();

        // Set the email from name/email to the current staff member's name/email
        if (($staff = $this->getStaff())) {
            $vars->from_name = $staff->first_name . ' ' . $staff->last_name;
            $vars->from_address = $staff->email;
        }

        return $vars;
    }

    /**
     * Returns a known subset of the available tags
     *
     * @return array An array of tags
     */
    private function getTags()
    {
        $options = Configure::get('Blesta.parser_options');
        $contact_tags = [
            'client.id_code', 'client.status', 'contact.first_name',
            'contact.last_name', 'contact.company', 'contact.address1',
            'contact.address2', 'contact.city', 'contact.state',
            'contact.country', 'contact.zip', 'contact.email',
            'contact.date_added'
        ];
        $service_tags = [
            'package.name', 'package.description', 'package.description_html',
            'package.status', 'service.name', 'service.date_added',
            'service.date_renews', 'service.date_last_renewed',
            'service.date_suspended', 'service.date_canceled',
            'service.status', 'module.name', 'module.label'
        ];

        // Set the tags to show
        $session = $this->read();
        $tags = ['client' => $contact_tags];
        if (isset($session['filters']['filter_services'])
            && $session['filters']['filter_services'] === 'true'
        ) {
            $tags['service'] = $service_tags;
        }


        // Add the encapsulation characters from the parser options to each tag
        // as well as the label definition
        $full_tags = [];
        foreach ($tags as $type => $list) {
            $fill_tags[$type] = [];

            foreach ($list as $tag) {
                $full_tag = $options['VARIABLE_START'] . $tag . $options['VARIABLE_END'];
                $full_tags[$type][$full_tag] = 'Tags.' . $tag;
            }
        }

        return $full_tags;
    }
}
