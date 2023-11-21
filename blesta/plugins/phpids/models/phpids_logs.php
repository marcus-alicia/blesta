<?php
/**
 * PHPIDS Logs
 *
 * @package blesta
 * @subpackage blesta.plugins.phpids
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PhpidsLogs extends PhpidsModel
{
    /**
     * Adds a intrusion request to the log
     *
     * @param array $vars An array of intrustion dection info including:
     *  - name The name of the event to log
     *  - value The value that violated a rule
     *  - uri The URI requested when the rule was violated
     *  - user_id The ID of the user that made the request
     *  - ip The IP address of the user that made the request
     *  - impact The impact rating of the violated rule
     *  - tags An array of tags triggered by request
     */
    public function add(array $vars)
    {
        $vars['date_added'] = $this->dateToUtc(date('c'));
        $vars['company_id'] = Configure::get('Blesta.company_id');
        $vars['tags'] = implode(', ', $vars['tags'] ?? []);

        $fields = ['company_id', 'name', 'value', 'uri', 'user_id',
            'ip', 'impact', 'tags', 'date_added'];

        $this->Record->insert('log_phpids', $vars, $fields);
    }

    /**
     * Fetches a list of PHPIDS log entries
     *
     * @param int $page The page of results to fetch
     * @param string $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array An array of stdClass objects, each representing a PHPIDS log entry
     */
    public function getList($page = 1, array $order = ['date_added' => 'DESC'])
    {
        $this->Record = $this->getLogs();

        return $this->Record->order($order)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
    }

    /**
     * The total number of results in the set of results
     *
     * @return int The total number of results in the set of results
     */
    public function getListCount()
    {
        return $this->getLogs()->numResults();
    }

    /**
     * Return a partial Record object query to fetch logs
     *
     * @return stdClass A stdClass object representing a PHPIDS Log entry
     */
    private function getLogs()
    {
        return $this->Record->select(['log_phpids.*'])->from('log_phpids')->
            where('log_phpids.company_id', '=', Configure::get('Blesta.company_id'));
    }

    /**
     * Rotate logs
     *
     * @param int $company_id The ID of the company
     * @param int $days The number of days to maintain logs
     */
    public function rotate($company_id, $days)
    {
        $this->Record->from('log_phpids')
            ->where('log_phpids.company_id', '=', $company_id)
            ->where(
                'log_phpids.date_added',
                '<',
                $this->dateToUtc(date('c', strtotime('-' . $days . ' days')))
            )
            ->delete();
    }
}
