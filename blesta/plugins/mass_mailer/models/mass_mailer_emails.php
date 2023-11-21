<?php

use Blesta\MassMailer\Traits\Parser;

/**
 * MassMailerEmails model
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.models
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerEmails extends MassMailerModel
{
    use Parser;

    /**
     * Creates an email for the given job
     *
     * @param int $job_id The ID of the job
     * @param array $vars An array of input including:
     *  - from_name The name of the sender
     *  - from_address The email address of the sender
     *  - subject The subject of the email
     *  - text The text version of the email body
     *  - html The HTML version of the email body
     *  - log Whether or not to log the email when sent
     * @return mixed The ID of the email created, or void on error
     */
    public function add($job_id, array $vars)
    {
        $vars['job_id'] = $job_id;
        $this->Input->setRules($this->getRules());

        if ($this->Input->validates($vars)) {
            // Ensure the log value is an integer
            if (array_key_exists('log', $vars)) {
                $vars['log'] = ($vars['log'] ? 1 : 0);
            }

            $fields = ['job_id', 'from_name', 'from_address', 'subject', 'text', 'html', 'log'];
            $this->Record->insert('mass_mailer_emails', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Retrieves the email associated with the given job
     *
     * @param int $job_id The ID of the job
     * @return mixed An stdClass object representing the email, or false if not found
     */
    public function getByJob($job_id)
    {
        return $this->Record->select()
            ->from('mass_mailer_emails')
            ->where('job_id', '=', $job_id)
            ->fetch();
    }

    /**
     * Validates whether the given email fields are acceptable
     *
     * @param array $vars An array of email fields to validate
     * @return bool True if the data is valid, or false otherwise
     */
    public function validate(array $vars)
    {
        $this->Input->setRules($this->getRules(false));
        return $this->Input->validates($vars);
    }

    /**
     * Generates a parsed sample subject, HTML, and text content of an email
     * based on the given contact and email information
     *
     * @param array $email An array containing the email content:
     *  - subject The email subject
     *  - html The email HTML content
     *  - text The email text content
     * @param stdClass $contact An stdClass object representing the contact
     * @return stdClass An stdClass object containing:
     *  - subject The email subject
     *  - html The email HTML content
     *  - text The email text content
     */
    public function getSample(array $email, stdClass $contact)
    {
        $content = (object)[
            'subject' => $email['subject'],
            'html' => $email['html'],
            'text' => $email['text']
        ];

        // Choose a service from the contact to use for tags
        $service_id = null;
        if (!empty($contact->service_ids)) {
            $service_ids = explode(',', $contact->service_ids);
            $service_id = (!empty($service_ids[0]) ? $service_ids[0] : null);
        }

        $tags = $this->getDefaultTags();
        $tags = array_merge($this->getContactTags($contact->id, $service_id), $tags);

        $parser = $this->getParser();
        $parser_options = $this->getParserOptions();

        // Parse email subject and body using template parser
        $content->text = $parser->parseString($content->text, $parser_options['text'])
            ->render($tags);
        $content->html = $parser->parseString($content->html, $parser_options['html'])
            ->render($tags);
        $content->subject = $parser->parseString($content->subject, $parser_options['text'])
            ->render($tags);

        // Set text from HTML if none exist
        $content->text = $this->htmlToText($content->html, $content->text);

        return $content;
    }

    /**
     * Sends the given email to the contact from the given task
     *
     * @param stdClass $task An stdClass object representing the mass mailer task
     *  - contact_id The ID of the contact
     *  - service_id The ID of the contact's service
     * @param stdClass $email An stdClass object representing the mass mailer email
     *  - from_name The email from name
     *  - from_address The from email address
     *  - subject The email's subject
     *  - text The email's text copy
     *  - html The email's HTML copy
     *  - log Whether to log the email when sent
     */
    public function send(stdClass $task, stdClass $email)
    {
        Loader::loadModels($this, ['Emails']);

        // Fetch the email tags
        $default_tags = $this->getDefaultTags();
        $tags = array_merge(
            $this->getContactTags($task->contact_id, $task->service_id),
            $default_tags
        );

        // Must have an email address to send to
        if (!isset($tags['contact']) || !isset($tags['contact']->email)) {
            return;
        }

        $options = [
            'log' => ($email->log == 1),
            'to_client_id' => $tags['contact']->client_id
        ];

        // Send the email
        $this->Emails->sendCustom(
            $email->from_address,
            $email->from_name,
            $tags['contact']->email,
            $email->subject,
            ['text' => $email->text, 'html' => $email->html],
            $tags,
            null,
            null,
            null,
            $options
        );
    }

    /**
     * Converts the given HTML to text if the given text contains no characters
     *
     * @param string $html The HTML to convert to text
     * @param string $text The current text, if any
     * @return string The HTML converted to text, or the original text given
     */
    public function htmlToText($html, $text = '')
    {
        // Convert HTML to text if no text version is available
        if (empty($text) || trim($text) === '') {
            if (!isset($this->Html2text)) {
                Loader::loadHelpers($this, ['TextParser']);
                $this->Html2text = $this->TextParser->create('html2text');
            }

            $this->Html2text->setHtml($html);
            $text = $this->Html2text->getText();
        }

        return $text;
    }

    /**
     * Retrieves the rules for validating email fields
     *
     * @param bool $require_job True to validate the job, otherwise validates only email fields
     * @return array An array of input validation rules
     */
    private function getRules($require_job = true)
    {
        $rules = [
            'from_name' => [
                'empty' => [
                    'rule' => ['isEmpty'],
                    'negate' => true,
                    'message' => $this->_('MassMailerEmails.!error.from_name.empty')
                ]
            ],
            'from_address' => [
                'valid' => [
                    'rule' => ['isEmail'],
                    'message' => $this->_('MassMailerEmails.!error.from_address.valid')
                ]
            ],
            'subject' => [
                'empty' => [
                    'rule' => ['isEmpty'],
                    'negate' => true,
                    'message' => $this->_('MassMailerEmails.!error.subject.empty')
                ]
            ],
            'html' => [
                'empty' => [
                    'rule' => ['isEmpty'],
                    'negate' => true,
                    'message' => $this->_('MassMailerEmails.!error.html.empty')
                ]
            ],
            'log' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => function($log) {
                        return in_array($log, [0, 1]);
                    },
                    'message' => $this->_('MassMailerEmails.!error.log.valid')
                ]
            ]
        ];

        if ($require_job) {
            $rules['job_id'] = [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'mass_mailer_jobs'],
                    'message' => $this->_('MassMailerEmails.!error.job_id.valid')
                ],
                'unique' => [
                    'rule' => [[$this, 'validateUniqueEmail']],
                    'message' => $this->_('MassMailerEmails.!error.job_id.unique')
                ]
            ];
        }

        return $rules;
    }

    /**
     * Validates that the given job is not associated with an email
     *
     * @param int $job_id The ID of the job
     * @return bool True if the job does not have an email, or false otherwise
     */
    public function validateUniqueEmail($job_id)
    {
        $total = $this->Record->select(['id'])
            ->from('mass_mailer_emails')
            ->where('job_id', '=', $job_id)
            ->numResults();

        return ($total === 0);
    }
}
