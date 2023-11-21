<?php

/**
 * Call data feed endpoints from an external website
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Feed extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        Language::loadLang(['feed']);
    }

    /**
     * Processes the request for the desired data feed
     */
    public function index()
    {
        $this->uses(['DataFeeds', 'Companies']);
        $this->components(['SettingsCollection']);

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Check if data feeds are enabled
        if (!isset($company_settings['enable_data_feeds']) || $company_settings['enable_data_feeds'] == '0') {
            echo $this->formatResponse(Language::_('Feed.!error.disabled', true));

            return false;
        }

        $feed = $this->get[0] ?? null;
        $endpoint = $this->get[1] ?? null;
        $response = $this->DataFeeds->execute($feed, $endpoint, $this->get, Configure::get('Blesta.company_id'));

        header('Content-Type: text/javascript');

        if (is_null($response)) {
            echo $this->formatResponse(Language::_('Feed.!error.invalid', true));
        } else {
            echo $this->formatResponse($response);
        }

        return false;
    }

    /**
     * Formats a data feed endpoint response in to Javascript
     *
     * @param string $response The response of the data feed endpoint
     * @return string The formatted response
     */
    private function formatResponse($response)
    {
        // Encode response
        $response = base64_encode($response);

        return 'document.write(atob("' . $response . '"));';
    }
}
