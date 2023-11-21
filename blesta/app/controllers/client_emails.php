<?php

/**
 * Client emails controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientEmails extends ClientController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Clients', 'Emails']);
        $this->StringHelper = $this->DataStructure->create('String');
    }

    /**
     * List email history
     */
    public function index()
    {
        // Set current page of results
        $sort_options = ['date_sent', 'subject'];
        $order_options = ['desc', 'asc'];
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
        $sort = (isset($this->get['sort']) && in_array($this->get['sort'], $sort_options)
            ? $this->get['sort']
            : 'date_sent'
        );
        $order = (isset($this->get['order']) && in_array($this->get['order'], $order_options)
            ? $this->get['order']
            : 'desc'
        );

        // Retrieve all sent email logs
        $sent = 1;
        $logs = $this->Clients->getMailLogList($this->client->id, $page, [$sort => $order], $sent);

        // Format CC addresses, if available
        if ($logs) {
            // Fetch email signatures
            $email_signatures = $this->Emails->getAllSignatures($this->client->company_id);
            $signatures = [];
            foreach ($email_signatures as $signature) {
                $signatures[] = $signature->text;
            }

            for ($i = 0, $num_logs = count($logs); $i < $num_logs; $i++) {
                // Convert email HTML to text if necessary
                $logs[$i]->body_text  = $this->StringHelper->removeFromText($logs[$i]->body_text, $signatures);
                if (empty($logs[$i]->body_text) && !empty($logs[$i]->body_html)) {
                    $logs[$i]->body_text = $this->StringHelper->htmlToText($logs[$i]->body_html);
                }

                // Format all CC addresses from CSV to array
                $cc_addresses = $logs[$i]->cc_address;
                $logs[$i]->cc_address = [];
                foreach (explode(',', $cc_addresses) as $address) {
                    if (!empty($address)) {
                        $logs[$i]->cc_address[] = $address;
                    }
                }
            }
        }

        $this->set('client', $this->client);
        $this->set('logs', $logs);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $this->Clients->getMailLogListCount($this->client->id, $sent),
                'uri' => $this->base_uri . 'emails/index/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[1]) || isset($this->get['sort']))
            );
        }
    }
}
