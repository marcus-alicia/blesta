<?php

use Blesta\Consoleation\Console;

/**
 * Allows users to interact with the upgrade process via the web or via command
 * line.
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminUpgrade extends AppController
{
    /**
     * @var string The current database version
     */
    private $database_version;

    /**
     * @var string The current file version
     */
    private $file_version;

    /**
     * Setup / Ensure logged in if non-CLI request
     */
    public function preAction()
    {
        // Load the session component
        if (!isset($this->Session)) {
            $this->components(['Session']);
        }

        $this->setDefaultView('default');

        $this->components(['Upgrades']);
        $this->uses(['Settings']);

        $company = $this->getCompany();
        $this->primeCompany($company);

        // Fetch the license info
        $data = $this->Settings->getSetting('database_version');

        $this->file_version = BLESTA_VERSION;

        if ($data) {
            $this->database_version = preg_replace('/(.*)\.(.*)\.(.*)[-|\.](.*)/iU', '$1.$2.$3-$4', $data->value);
        } else {
            $versions = array_keys($this->Upgrades->getMappings());
            $this->database_version = $versions[0];
            unset($version);
        }
    }

    /**
     * Post-action
     */
    public function postAction()
    {
    }

    /**
     * Override AppController::primeCompany
     *
     * @param stdClass $company A stdClass object representing the company to prime
     */
    protected function primeCompany($company)
    {
        if (!isset($this->Companies)) {
            $this->uses(['Companies']);
        }

        $this->company_id = $company->id;

        // Set all company info
        Configure::set('Blesta.company', $company);
        // Set the company ID in use for the current instance
        Configure::set('Blesta.company_id', $this->company_id);
    }

    /**
     * Process upgrade
     */
    public function index()
    {

        // Process the upgrade via CLI
        if ($this->is_cli) {
            return $this->processCli();
        }

        // Only allow to continue if upgrade is possible
        if (version_compare($this->database_version, $this->file_version, '>=')) {
            $this->redirect($this->base_uri);
        }
    }

    /**
     * Process GUI upgrade
     */
    public function process()
    {
        // Process the upgrade if set to do so
        if (!empty($this->post)) {
            $this->Upgrades->start($this->database_version, $this->file_version);

            if (($errors = $this->Upgrades->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage('message', 'The upgrade completed successfully.');
                $this->redirect($this->base_uri . ($this->isLoggedIn() ? '' : 'login/'));
            }
        }
    }

    /**
     * Process the upgrade via command line
     */
    private function processCli()
    {
        // Initialize the console
        $this->Console = new Console();

        // Welcome message
        $this->Console->output("%s\nBlesta CLI Upgrader\n%s\n", str_repeat('-', 40), str_repeat('-', 40));

        if (version_compare($this->database_version, $this->file_version, '>=')) {
            $this->Console->output("Nothing to upgrade.\n");
            exit;
        }

        $this->Console->output('Upgrade from ' . $this->database_version . ' to ' . $this->file_version . '? (Y/N): ');
        if (strtolower(substr($this->Console->getLine(), 0, 1)) != 'y') {
            $this->Console->output("Upgrade will not be performed. Goodbye.\n");
            exit;
        }

        $this->Upgrades->start($this->database_version, $this->file_version, [$this->Console, 'progressBar']);

        if (($errors = $this->Upgrades->errors())) {
            $this->Console->output("Upgrade could not complete, the following errors occurred:\n");
            foreach ($errors as $key => $value) {
                $this->Console->output(implode("\n", $value) . "\n");
            }
            exit(1);
        } else {
            $this->Console->output("\nFinished.\n");
        }

        return false;
    }
}
