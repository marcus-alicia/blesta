<?php
Loader::load(COMPONENTDIR . 'reports' . DS . 'report_interface.php');

/**
 * Factory class for creating Report objects
 *
 * @package blesta
 * @subpackage blesta.components.reports
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Reports
{
    /**
     * Returns an instance of the requested report
     *
     * @param string $report_name The name of the report to instantiate
     * @return mixed An object of type $report_name
     * @throws Exception Thrown when the report does not exist or does not inherit from the appropriate parent
     */
    public static function create($report_name)
    {
        $report_name = Loader::toCamelCase($report_name);
        $report_file = Loader::fromCamelCase($report_name);

        if (!Loader::load(COMPONENTDIR . 'reports' . DS . $report_file . DS . $report_file . '.php')) {
            throw new Exception("Report '" . $report_name . "' does not exist.");
        }

        if (class_exists($report_name)
            && array_key_exists('ReportInterface', class_implements($report_name, false))
        ) {
            return new $report_name();
        }

        throw new Exception("Report '" . $report_name . "' is not a recognized report.");
    }
}
