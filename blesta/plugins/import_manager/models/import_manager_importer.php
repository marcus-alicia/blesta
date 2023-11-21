<?php
/**
 * Import Manager Importer
 *
 * @package blesta
 * @subpackage blesta.plugins.importer_manager.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ImportManagerImporter extends ImportManagerModel
{
    /**
     * Returns all migrators available
     *
     * @return array An array of migrators
     */
    public function getMigrators()
    {
        $migrator_dir = PLUGINDIR . 'import_manager' . DS . 'components' . DS . 'migrators';
        $migrators = [];

        $files = scandir($migrator_dir);
        foreach ($files as $file) {
            if (substr($file, 0, 1) != '.' && is_dir($migrator_dir . DS . $file)) {
                $versions = $this->getVersions($file);
                if (!empty($versions)) {
                    $migrators[$file] = json_decode(
                        file_get_contents($migrator_dir . DS . $file . DS . 'info.json')
                    );
                    $migrators[$file]->versions = $versions;
                }
            }
        }

        ksort($migrators);

        return $migrators;
    }

    /**
     * Return a migrator
     *
     * @return mixed A stdClass object representing the migrator, null if it does not exist
     */
    public function getMigrator($type)
    {

        $migrator_dir = PLUGINDIR . 'import_manager' . DS . 'components' . DS . 'migrators';
        $migrator = null;
        $versions = $this->getVersions($type);
        if (!empty($versions)) {
            $migrator = json_decode(file_get_contents($migrator_dir . DS . $type . DS . 'info.json'));
            $migrator->versions = $versions;
        }

        return $migrator;
    }

    /**
     * Returns all versions available for the given migrator type
     *
     * @param string $type The migrator type
     * @return array An array of versions available
     */
    public function getVersions($type)
    {
        $migrator_dir = PLUGINDIR . 'import_manager' . DS . 'components' . DS . 'migrators' . DS . $type;
        $migrator = json_decode(file_get_contents($migrator_dir . DS . 'info.json'));

        $versions = [];
        $files = scandir($migrator_dir);
        foreach ($files as $file) {
            if (substr($file, 0, 1) != '.' && is_dir($migrator_dir . DS . $file)) {
                $versions[$file] = isset($migrator->migrators->{$file}) ? $migrator->migrators->{$file} : $file;
            }
        }

        asort($versions);

        return $versions;
    }

    /**
     * Runs the migrator
     *
     * @param string $type The type of migrator (dir in /import_manager/components/migrators/)
     * @param string $version The version of the migrator to run (dir in /import_manager/components/migrators/$type/)
     * @param array $vars An array of migrator specific details
     * @param array $custom A key/value pair of custom inputs
     */
    public function runMigrator($type, $version, array $vars)
    {
        Loader::loadComponents($this, ['ImportManager.Migrators']);

        try {
            $migrator = $this->Migrators->create($type, $version, [$this->Record]);
            $migrator->processSettings($vars);

            if (($errors = $migrator->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            $migrator->processConfiguration($vars);

            if (($errors = $migrator->errors())) {
                $this->Input->setErrors($errors);
                return;
            }
        } catch (Exception $e) {
            $this->Input->setErrors(['error' => [$e->getMessage()]]);
            return;
        }

        $migrator->import();

        if (($errors = $migrator->errors())) {
            $this->Input->setErrors($errors);
        }
    }
}
