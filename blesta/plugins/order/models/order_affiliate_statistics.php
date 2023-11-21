<?php
/**
 * Order Affiliate Statistics Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliateStatistics extends OrderModel
{
    /**
     * Get the affiliate statistics from a specific day or a given date range
     *
     * @param array $filters A list of filters for the query
     *
     *  - affiliate_id The ID of the affiliate from which to obtain the statistics
     *  - start_date The start date from which to obtain the statistics
     *  - end_date The end date from which to obtain the statistics
     * @return array A numerically indexed array containing the statistics objects each containing:
     *
     *  - affiliate_id The ID of the affiliate
     *  - visits The number of registered visits for a given day
     *  - sales The number of registered sales for a given day
     *  - date The date for which these statistics are recorded
     */
    public function get(array $filters = [])
    {
        $filters['start_date'] = $this->Date->format('Y-m-d', (isset($filters['start_date']) ? $filters['start_date'] : null));
        $filters['end_date'] = $this->Date->format('Y-m-d', (isset($filters['end_date']) ? $filters['end_date'] : null));

        return $this->Record->select()
            ->from('order_affiliate_statistics')
            ->where('order_affiliate_statistics.affiliate_id', '=', $filters['affiliate_id'])
            ->where('order_affiliate_statistics.date', '>=', $this->dateToUtc($filters['start_date'] . ' 00:00:00'))
            ->where('order_affiliate_statistics.date', '<=', $this->dateToUtc($filters['end_date'] . ' 23:59:59'))
            ->fetchAll();
    }

    /**
     * Get the total count of statistics from a specific affiliate
     *
     * @param int $affiliate_id The ID of the affiliate from which to obtain the statistics
     * @return array A numerically indexed array of objects containing the following properties:
     *
     *  - visits The total count of registered visits
     *  - sales The total count of registered sales
     */
    public function getAllCount($affiliate_id)
    {
        $select = [
            'SUM(order_affiliate_statistics.visits)' => 'visits',
            'SUM(order_affiliate_statistics.sales)' => 'sales'
        ];

        return $this->Record->select($select)
            ->from('order_affiliate_statistics')
            ->where('order_affiliate_statistics.affiliate_id', '=', $affiliate_id)
            ->fetch();
    }

    /**
     * Register an affiliate visit
     *
     * @param int $affiliate_id The ID of the affiliate to register a referral visit
     * @param string $date The date on which the visit is registered
     */
    public function registerVisit($affiliate_id, $date = null)
    {
        $date = $this->Date->format('Y-m-d H:i:s', $date);
        $stats = $this->get(['affiliate_id' => $affiliate_id, 'start_date' => $date]);

        $vars = [
            'visits' => 1,
            'date' => $this->dateToUtc($date)
        ];

        if (count($stats) >= 1) {
            $vars['visits'] = $stats[0]->visits + 1;
            $this->Record->where('affiliate_id', '=', $affiliate_id)
                ->where('date', '=', $stats[0]->date)
                ->update('order_affiliate_statistics', $vars);
        } else {
            $vars['affiliate_id'] = $affiliate_id;
            $this->Record->insert('order_affiliate_statistics', $vars);
        }
    }

    /**
     * Register an affiliate sale
     *
     * @param int $affiliate_id The ID of the affiliate to register a referral sale
     * @param string $date The date on which the sale is registered
     */
    public function registerSale($affiliate_id, $date = null)
    {
        $date = $this->Date->format('Y-m-d H:i:s', $date);
        $stats = $this->get(['affiliate_id' => $affiliate_id, 'start_date' => $date]);

        $vars = [
            'sales' => 1,
            'date' => $this->dateToUtc($date)
        ];

        if (count($stats) >= 1) {
            $vars['sales'] = $stats[0]->sales + 1;
            $this->Record->where('affiliate_id', '=', $affiliate_id)
                ->where('date', '=', $stats[0]->date)
                ->update('order_affiliate_statistics', $vars);
        } else {
            $vars['affiliate_id'] = $affiliate_id;
            $this->Record->insert('order_affiliate_statistics', $vars);
        }
    }
}
