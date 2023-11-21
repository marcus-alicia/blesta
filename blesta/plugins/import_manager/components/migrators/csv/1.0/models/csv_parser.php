<?php
/**
 * Generic CSV Parser Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.csv
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CsvParser
{
    /**
     * Get all data columns.
     *
     * @param mixed $data
     * @return mixed An array containing the CSV columns, false if an error occurs
     */
    public function getColumns($data)
    {
        if ($this->validateData($data)) {
            $rows = explode("\n", trim($data));
            $columns = explode(',', trim($rows[0]));

            return $columns;
        }

        return false;
    }

    /**
     * Get all data columns prepared for a select form.
     *
     * @param mixed $data
     * @return mixed An array containing the CSV columns, false if an error occurs
     */
    public function getColumnsSelect($data)
    {
        if ($this->validateData($data)) {
            $rows = explode("\n", trim($data));
            $columns = explode(',', trim($rows[0]));
            $fields = [];

            foreach ($columns as $column) {
                $fields[$column] = $column;
            }

            return $fields;
        }

        return false;
    }

    /**
     * Get all data rows.
     *
     * @param mixed $data
     * @return mixed An array containing the CSV rows, false if an error occurs
     */
    public function getRows($data)
    {
        if ($this->validateData($data)) {
            $columns = $this->getColumns($data);
            $rows = explode("\n", trim($data));
            unset($rows[0]);

            foreach ($rows as $key => $value) {
                $value = explode(',', $value);
                $fields = [];

                foreach ($value as $field_id => $field_value) {
                    $fields[$columns[$field_id]] = $field_value;
                }

                $rows[$key] = $fields;
            }

            return $rows;
        }

        return false;
    }

    /**
     * Check if the data is valid.
     *
     * @param string $data The CSV data to validate
     * @return bool True if the data is valid
     */
    public function validateData($data)
    {
        $rows = explode("\n", trim($data));

        if (is_array($rows) && count($rows) > 1) {
            $columns = explode(',', $rows[0]);

            return is_array($columns) && count($columns) > 1;
        }

        return false;
    }
}
