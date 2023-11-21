<?php
namespace Blesta\Proration;

/**
 * Proration calculator
 *
 * Supports Year and Month periods
 */
class Proration
{
    const PERIOD_YEAR = 'year';
    const PERIOD_MONTH = 'month';
    const PERIOD_WEEK = 'week';
    const PERIOD_DAY = 'day';
    const PERIOD_ONETIME = 'onetime';

    /**
     * @var array List of proratable period
     */
    protected $proratable_periods = [
        self::PERIOD_YEAR,
        self::PERIOD_MONTH
    ];

    /**
     * @var string Start date
     */
    protected $start_date;

    /**
     * @var int The day to prorate to
     */
    protected $prorate_day;

    /**
     * @var string The date to prorate to
     */
    protected $prorate_date;

    /**
     *
     * @var int The term
     */
    protected $term;

    /**
     * @var string The period
     */
    protected $period;

    /**
     * @var string The time zone
     */
    protected $time_zone;

    /**
     * Initialize proration
     *
     * @param string $start_date  The date to prorate in ISO 8601 format
     * @param int    $prorate_day The day of the month to prorate to
     * @param int    $term        The term
     * @param string $period      The period for the term
     */
    public function __construct($start_date, $prorate_day, $term, $period)
    {
        $this->start_date = $start_date;
        $this->prorate_day = (int) $prorate_day;
        $this->term = (int) $term;
        $this->period = $period;
    }

    /**
     * Set valid proratable periods
     *
     * @param array $periods All periods to allow proration on
     * @return \Proration
     */
    public function setProratablePeriods(array $periods)
    {
        $this->proratable_periods = $periods;
        return $this;
    }

    /**
     * Set the timezone to use for date calculations
     *
     * @param string $time_zone
     * @return \Proration
     */
    public function setTimeZone($time_zone)
    {
        $this->time_zone = $time_zone;
        return $this;
    }

    /**
     * Set the date to prorate to
     *
     * @param string $date
     * @return \Proration
     */
    public function setProrateDate($date)
    {
        $this->prorate_date = $date;
        return $this;
    }

    /**
     * Fetches the start date
     *
     * @return string The start date in use
     */
    public function startDate()
    {
        return $this->start_date;
    }

    /**
     * Fetches the prorate day
     *
     * @return int The prorate day in use
     */
    public function prorateDay()
    {
        return $this->prorate_day;
    }

    /**
     * Fetches the term
     *
     * @return int The term in use
     */
    public function term()
    {
        return $this->term;
    }

    /**
     * Fetches the period
     *
     * @return string The period in use
     */
    public function period()
    {
        return $this->period;
    }

    /**
     * Fetches the date to prorate to
     *
     * @return mixed A string containing the date to prorate to
     */
    public function prorateDate()
    {
        $cur_time_zone = date_default_timezone_get();

        if (null !== $this->time_zone) {
            date_default_timezone_set($this->time_zone);
        }

        $date = null;
        if ($this->prorate_date) {
            $date = $this->prorateDateFromDate();
        } else {
            $date = $this->prorateDateFromDay();
        }

        date_default_timezone_set($cur_time_zone);
        return $date;
    }

    /**
     * Calculate the prorate date from the given date.
     *
     * @return string The prorate date
     */
    protected function prorateDateFromDate()
    {
        return date('c', strtotime('midnight', strtotime($this->prorate_date)));
    }
    /**
     * Calculates the prorate date from the given day of the month
     *
     * @return string The prorate date
     */
    protected function prorateDateFromDay()
    {
        if ($this->prorate_day <= 0 || !in_array($this->period, $this->proratable_periods)) {
            return null;
        }

        // Fetch time zone offset of given date
        $offset = substr($this->start_date, 19);

        $start_time = strtotime($this->start_date);
        $current_day = date('j', $start_time);
        $days_in_month = date('t', $start_time);

        $result = null;

        if ($current_day != $this->prorate_day) {
            $first = date('c', strtotime('-' . ($current_day-1) . ' days', $start_time));
            $next_first = strtotime($first . ' + 1 month');

            $time = strtotime($first);
            $day = $this->prorate_day;

            if ($day > $days_in_month) {
                $day = $days_in_month;
            } elseif ($day < $current_day) {
                $time = $next_first;
            }

            $result = date(
                'c',
                strtotime('+' . ($day-1) . ' days', strtotime('midnight', $time))
            );

            // Set original offset, no timezone given
            if (null === $this->time_zone) {
                $result = substr($result, 0, -6) . $offset;
            }
        }

        return $result;
    }

    /**
     * Determine if proration can occur
     *
     * @return bool True if proration can occur, false otherwise
     */
    public function canProrate()
    {
        return $this->prorateDays() > 0;
    }

    /**
     * Calculates the number of days to prorate
     *
     * @param  string $from_date The from date
     * @param  string $to_date   The to date
     * @return int The number of days to prorate
     */
    public function prorateDays()
    {
        $to_date = $this->prorateDate();

        if (!$to_date) {
            return 0;
        }

        return $this->daysDiff($this->start_date, $to_date);
    }

    /**
     * Calculate the number of days between two dates
     *
     * @param  string $from
     * @param  string $to
     * @return int
     */
    protected function daysDiff($from, $to)
    {
        $second_per_day = 86400;
        return (int) round(abs(strtotime($from) - strtotime($to)) / $second_per_day);
    }

    /**
     * Calculate the prorated price
     *
     * @param  float $price     The price for a full period
     * @param  int   $precision The number of decimal places of percision
     * @return float The prorated price
     */
    public function proratePrice($price, $precision = 4)
    {
        $days_in_term = $this->daysDiff(
            $this->start_date,
            date(
                'c',
                strtotime($this->start_date . ' + ' . $this->term . ' ' . $this->period)
            )
        );
        $prorate_days = $this->prorateDays();

        if ($days_in_term > 0) {
            return round($prorate_days * $price / $days_in_term, $precision);
        }

        return 0.0;
    }
}
