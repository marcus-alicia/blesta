<?php
/**
 * Upgrades to version 4.5.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_5_0B1 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Configure::load('blesta');
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @return array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addProxySetting',
            'updateInvoiceTerms',
            'addPackageNames',
            'addPackageDescriptions',
            'addPackageGroupNames',
            'addPackageGroupDescriptions',
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Adds new package_options.type values (text, textarea, password)
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addProxySetting($undo = false)
    {
        $setting = 'behind_proxy';

        if ($undo) {
            $this->Record->query('DELETE `settings`.* FROM `settings` WHERE `key` = ?', [$setting]);
        } else {
            // Create the new setting
            $query = 'INSERT INTO `settings` (`key`, `value`, `encrypted`, `comment`, `inherit`) VALUES (?,?,?,?,?)';
            $this->Record->query($query, [$setting, 'false', 0, null, 1]);
        }
    }

    /**
     * Updates company settings to set invoice terms for all installed languages
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateInvoiceTerms($undo = false)
    {
        $setting = 'inv_terms';

        if ($undo) {
            $this->Record->query(
                'UPDATE `company_settings` SET `key` = ? WHERE `key` = ?;',
                [$setting, $setting . '_en_us']
            );
            $this->Record->query(
                'DELETE `company_settings`.* FROM `company_settings` WHERE `key` LIKE ?',
                [$setting . '_%']
            );
        } else {
            // Create the new setting
            $query = "INSERT INTO `company_settings` (`key`, `company_id`, `value`, `encrypted`, `inherit`)
                SELECT CONCAT(?, `languages`.`code`), `company_settings`.`company_id`,
                    `company_settings`.`value`, `company_settings`.`encrypted`, `company_settings`.`inherit`
                FROM `company_settings`
                INNER JOIN `languages` ON `languages`.`company_id` = `company_settings`.`company_id`
                WHERE `company_settings`.`key` = ?;";
            $this->Record->query($query, [$setting . '_', $setting]);
            $this->Record->query('DELETE `company_settings`.* FROM `company_settings` WHERE `key` = ?;', [$setting]);
        }
    }

    /**
     * Add a new package_group_descriptions table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageGroupDescriptions($undo = false)
    {
        if ($undo) {
            // Copy description over from the package_group_descriptions table
            $this->Record->query(
                'UPDATE `package_groups`
                INNER JOIN `package_group_descriptions`
                    ON `package_group_descriptions`.`package_group_id` = `package_groups`.`id`
                SET `package_groups`.`description` = `package_group_descriptions`.`description`
                WHERE `package_group_descriptions`.`lang` = ?',
                ['en_us']
            );

            // Drop the package_group_descriptions table
            $this->Record->query(
                'DROP TABLE `package_group_descriptions`'
            );
        } else {
            // Add the package_group_descriptions table
            $this->Record->setField('package_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('lang', ['type' => 'varchar', 'size' => 5])
                ->setField('description', ['type' => 'mediumtext', 'is_null' => true, 'default' => null])
                ->setKey(['package_group_id', 'lang'], 'primary')
                ->create('package_group_descriptions', true);

            foreach ($this->getAllLanguages() as $language) {
                // Copy description over from the package_groups table
                $this->Record->query(
                    'INSERT INTO `package_group_descriptions` (`package_group_id`, `lang`, `description`)
                    SELECT `id`, ?, `description` FROM `package_groups` WHERE `package_groups`.`company_id` = ?',
                    [$language->code, $language->company_id]
                );
            }
        }
    }

    /**
     * Add a new package_group_names table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageGroupNames($undo = false)
    {
        $this->addNames('package_group', $undo);
    }

    /**
     * Add a new package_descriptions table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageDescriptions($undo = false)
    {
        if ($undo) {
            // Copy descriptions over from the packages table
            $this->Record->query(
                'UPDATE `packages`
                INNER JOIN `package_descriptions` on `package_descriptions`.`package_id` = `packages`.`id`
                SET `packages`.`description` = `package_descriptions`.`text`,
                    `packages`.`description_html` = `package_descriptions`.`html`
                WHERE `package_descriptions`.`lang` = ?',
                ['en_us']
            );

            // Drop the package_descriptions table
            $this->Record->query(
                'DROP TABLE `package_descriptions`'
            );
        } else {
            // Add the package_descriptions table
            $this->Record->setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('lang', ['type' => 'varchar', 'size' => 5])
                ->setField('html', ['type' => 'mediumtext', 'is_null' => true, 'default' => null])
                ->setField('text', ['type' => 'mediumtext', 'is_null' => true, 'default' => null])
                ->setKey(['package_id', 'lang'], 'primary')
                ->create('package_descriptions', true);

            foreach ($this->getAllLanguages() as $language) {
                // Copy descriptions over from the packages table
                $this->Record->query(
                    'INSERT INTO `package_descriptions` (`package_id`, `lang`, `html`, `text`)
                    SELECT `packages`.`id`, ?, `packages`.`description_html`, `packages`.`description`
                    FROM `packages`
                    WHERE `packages`.`company_id` = ?
                    GROUP BY `packages`.`id`',
                    [$language->code, $language->company_id]
                );
            }
        }
    }

    /**
     * Add a new package_names table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageNames($undo = false)
    {
        $this->addNames('package', $undo);
    }

    /**
     * Adds the multilanguage name table for the given type
     *
     * @param string $table_prefix Which kind of tables are being effected package or package_group
     * @param bool $undo Whether to add or undo the change
     */
    private function addNames($table_prefix, $undo = false)
    {
        $table1 = $table_prefix . 's';
        $table2 = $table_prefix . '_names';
        $id_field = $table_prefix . '_id';
        if ($undo) {
            // Copy names over from table2
            $this->Record->query(
                'UPDATE `' . $table1 . '`
                INNER JOIN `' . $table2 . '` on `' . $table2 . '`.`' . $id_field . '` = `' . $table1 . '`.`id`
                SET `' . $table1 . '`.`name` = `' . $table2 . '`.`name`
                WHERE `' . $table2 . '`.`lang` = ?',
                ['en_us']
            );

            // Drop table2
            $this->Record->query(
                'DROP TABLE `' . $table2 . '`'
            );
        } else {
            // Add table2
            $this->Record->setField($id_field, ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('lang', ['type' => 'varchar', 'size' => 5])
                ->setField('name', ['type' => 'varchar', 'size' => 255])
                ->setKey([$id_field, 'lang'], 'primary')
                ->create($table2, true);

            foreach ($this->getAllLanguages() as $language) {
                // Copy names over from table1
                $this->Record->query(
                    'INSERT INTO `' . $table2 . '` (`' . $id_field . '`, `lang`, `name`)
                    SELECT `' . $table1 . '`.`id`, ?, `' . $table1 . '`.`name`
                    FROM `' . $table1 . '`' . ' WHERE `' . $table1 . '`.`company_id` = ?
                    GROUP BY `' . $table1 . '`.`id`',
                    [$language->code, $language->company_id]
                );
            }
        }
    }

    /**
     * Retrieves all languages
     *
     * @return array An array of stdClass objects each representing a language in the system
     */
    private function getAllLanguages()
    {
        return $this->Record->select()->from('languages')->fetchAll();
    }
}
