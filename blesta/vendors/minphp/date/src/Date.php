<?php
namespace Minphp\Date;

use DateTime;
use DateTimeZone;

/**
 * Provides methods useful in formatting dates and date timestamps.
 */
class Date
{
    const ATOM   = 'Y-m-d\TH:i:sP';
    const COOKIE = 'l, d-M-y H:i:s T';
    const ISO8601 = 'Y-m-d\TH:i:sO';
    const RFC822 = 'D, d M y H:i:s O';
    const RFC850 = 'l, d-M-y H:i:s T';
    const RFC1036 = 'D, d M y H:i:s O';
    const RFC1123 = 'D, d M Y H:i:s O';
    const RFC2822 = 'D, d M Y H:i:s O';
    const RFC3339 = 'Y-m-d\TH:i:sP';
    const RSS = 'D, d M Y H:i:s O';
    const W3C = 'Y-m-d\TH:i:sP';

    /**
     * @var array Common date formats, predefined for PHP's date function, overwritable
     * by the constructor
     */
    private $formats = array(
        'date' => 'F j, Y',
        'day' => 'l, F j, Y',
        'month' => 'F Y',
        'year' => 'Y',
        'date_time' => 'M d y g:i:s A',
    );
    /**
     * @var string The start timezone
     */
    private $timezone_from;
    /**
     * @var string The end timezone
     */
    private $timezone_to;

    /**
     * Constructs a new Date component using the given date formats in $formats.
     *
     * @param array $formats An array of key/value pairs of PHP date format strings with the following keys:
     *  - date A date
     *  - day A date with day reference
     *  - month A month and year date
     *  - year A year date only
     *  - date_time A date time
     * @see Date::cast()
     */
    public function __construct(array $formats = null, $timezone_from = null, $timezone_to = null)
    {
        $this->setFormats($formats);
        $this->setTimezone($timezone_from, $timezone_to);
    }

    /**
     * Set the current time zone to be used during date calculations
     *
     * @param string $from The timezone to convert from
     * @param string $to The timezone to convert to
     * @return this
     */
    public function setTimezone($from = null, $to = null)
    {
        $this->timezone_from = $from;
        $this->timezone_to = $to;
        return $this;
    }

    /**
     * Sets the formats to use as the pre-defined types.
     *
     * @param array $formats An array of key/value pairs of PHP date format strings with the following keys:
     *  - date A date
     *  - day A date with day reference
     *  - month A month and year date
     *  - year A year date only
     *  - date_time A date time
     * @return this
     */
    public function setFormats(array $formats = null)
    {
        $this->formats = array_merge($this->formats, (array)$formats);
        return $this;
    }

    /**
     * Format a date using one of the date formats provided to the constructor,
     * or predefined in this class.
     *
     * @param string $date The date string to cast into another format, also handles Unix time stamps
     * @param string $format A predefined date format in Date::$formats, a Date constant, or a date string.
     * @return string The date formatted using the given format rule, null on error
     */
    public function cast($date, $format = 'date')
    {
        return $this->format((isset($this->formats[$format]) ? $this->formats[$format] : $format), $date);
    }

    /**
     * Modifies and formats a date using one of the date formats provided to the constructor,
     * or predefined in this class.
     *
     * @param string $date The date string to cast into another format, also handles Unix time stamps
     * @param string $modifier The strtotime-compatible timestamp modifier to adjust the given $date by
     *  e.g. '+1 year'
     * @param string $format A predefined date format in Date::$formats, a Date constant, or a date string.
     * @param string $relative_from_timezone The timezone that the $date originally represented if it differs
     *  from the set 'from timezone'. Include a relative from timezone when the $date being modified may cross
     *  daylight savings and the set 'from timezone' is different from the set 'to timezone', otherwise the
     *  modified date may be off by the time change in daylight savings.
     * @return string The date modified and formatted using the given format rule, null on error
     */
    public function modify($date, $modifier, $format = 'date', $relative_from_timezone = null)
    {
        // Get the from timezone
        $tz_from = ($this->timezone_from ? $this->dateTimeZone($this->timezone_from) : null);

        // If the given date is representative of a time in a different timezone_from, convert it first
        if ($relative_from_timezone !== null && $relative_from_timezone !== $this->timezone_from) {
            // Create the from timezone as the relative timezone
            $tz_from = $this->dateTimeZone($relative_from_timezone);

            // Set the from date (order of setting timezone matters)
            $date_time = $this->dateTime($date);
            $date_time->setTimezone($tz_from);
        } else {
            // Set the from date
            $date_time = $this->dateTime($date, $tz_from);
        }

        // Modify the date from the from timezone
        $modified_date = $this->dateTime($date_time->format('c'));
        $modified_date->setTimezone($tz_from);
        $modified_date->modify($modifier);

        return $this->cast($modified_date->format('c'), $format);
    }

    /**
     * Format two dates to represent a range between them.
     *
     * @param string $start The start date
     * @param string $end The end date
     * @param array $formats An array of 'start' and 'end' indexes, supplying
     *  options for 'same_day', 'same_month', 'same_year', and 'other' formats.
     *  Select indexes can be supplied to overwrite only specific rules.
     * @return string The date range, null on error
     */
    public function dateRange($start, $end, $formats = null)
    {
        $default_formats = array(
            'start' => array(
                'same_day' => 'F j, Y',
                'same_month' => 'F j-',
                'same_year' => 'F j - ',
                'other' => 'F j, Y - '
            ),
            'end' => array(
                'same_day' => '',
                'same_month' => 'j, Y',
                'same_year' => 'F j, Y',
                'other' => 'F j, Y'
            )
        );

        $formats = $this->mergeArrays($default_formats, (array)$formats);

        // Set the start/end dates using the timezone from date
        $timezone = ($this->timezone_from ? $this->dateTimeZone($this->timezone_from) : null);
        $start_date = $this->dateTime($start, $timezone);
        $end_date = $this->dateTime($end, $timezone);
        $s_date = $start_date->format('Ymd');
        $e_date = $end_date->format('Ymd');

        if ($s_date == $e_date) {
            // Same day
            return $this->format($formats['start']['same_day'], $start)
                . $this->format($formats['end']['same_day'], $end);
        } elseif (substr($s_date, 0, 6) == substr($e_date, 0, 6)) {
            // Same month
            return $this->format($formats['start']['same_month'], $start)
                . $this->format($formats['end']['same_month'], $end);
        } elseif (substr($s_date, 0, 4) == substr($e_date, 0, 4)) {
            // Same year
            return $this->format($formats['start']['same_year'], $start)
                . $this->format($formats['end']['same_year'], $end);
        } else {
            // Other
            return $this->format($formats['start']['other'], $start) . $this->format($formats['end']['other'], $end);
        }
    }

    /**
     * Format a date using the supply date string
     *
     * @param string $format The format to use
     * @param string $date The date to format
     * @return string The formatted date
     */
    public function format($format, $date = null)
    {
        // Use current date/time if date is not given
        if ($date === null) {
            $date = time();
        }

        if ($date != '' && $format != '') {
            // Format the date
            $from_timezone = ($this->timezone_from ? $this->dateTimeZone($this->timezone_from) : null);
            $to_timezone = ($this->timezone_to ? $this->dateTimeZone($this->timezone_to) : null);
            $date_time = $this->dateTime($date, $from_timezone);

            if ($to_timezone) {
                $date_time->setTimezone($to_timezone);
            }

            return $date_time->format($format);
        }

        return null;
    }

    /**
     * Convert a date string to Unix time
     *
     * @param string A date string
     * @return int The Unix timestamp of the given date
     */
    public function toTime($date)
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }

        return $date;
    }

    /**
     * Returns an array of months in key/value pairs
     *
     * @param int $start The start month (1 = Jan, 12 = Dec)
     * @param int $end The end month
     * @param string $key_format The format for the key
     * @param string $value_format The format for the value
     * @return array An array of key/value pairs representing the range of months
     */
    public function getMonths($start = 1, $end = 12, $key_format = 'm', $value_format = 'F')
    {
        // Set the date using the timezone from date
        $timezone = ($this->timezone_from ? $this->dateTimeZone($this->timezone_from) : null);
        $date = $this->dateTime(null, $timezone);

        $months = array();
        for ($i = $start; $i <= $end; $i++) {
            $date->setDate($date->format('Y'), $i, 1);
            $months[$date->format($key_format)] = $date->format($value_format);
        }

        return $months;
    }

    /**
     * Returns an array of keys in key/value pairs
     *
     * @param int $start The 4-digit start year
     * @param int $end The 4-digit end year
     * @param string $key_format The format for the key
     * @param string $value_format The format for the value
     * @return array An array of key/value pairs representing the range of years
     */
    public function getYears($start, $end, $key_format = 'y', $value_format = 'Y')
    {
        // Set the date using the timezone from date
        $timezone = ($this->timezone_from ? $this->dateTimeZone($this->timezone_from) : null);
        $date = $this->dateTime(null, $timezone);

        $years = array();
        for ($i = $start; $i <= $end; $i++) {
            $date->setDate($i, 1, 1);
            $years[$date->format($key_format)] = $date->format($value_format);
        }

        return $years;
    }

    /**
     * Retrieve all timezones or those for a specific country
     *
     * @param string $country The ISO 3166-1 2-character country code to fetch
     *  timezone information for (PHP 5.3 or greater)
     * @return array An array of all timezones (or those for the given country)
     *  indexed by primary locale, then numerically indexed for each timezone in that locale
     */
    public function getTimezones($country = null)
    {
        // Hold the array of timezone data
        $tz_data = array();

        $accepted_zones = array_flip(array(
            'Africa',
            'America',
            'Antarctica',
            'Arctic',
            'Asia',
            'Atlantic',
            'Australia',
            'Europe',
            'Indian',
            'Pacific',
            'UTC'
        ));

        if ($country) {
            $listing = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country);
        } else {
            $listing = DateTimeZone::listIdentifiers();
        }
        $num_listings = count($listing);

        // Associate each timezone identifier with its meta data
        for ($i=0; $i<$num_listings; $i++) {
            // Convert timezone identifier to timezone array
            $zone = $this->dateTimeZone($listing[$i]);
            $zone_info = $zone->getTransitions(time(), time());

            $timezone = $this->timezoneFromIdentifier($zone_info[0], $listing[$i]);
            $primary_zone_name = isset($timezone['zone'][0]) ? $timezone['zone'][0] : false;

            // Only allow accepted zones into the listing
            if (!isset($accepted_zones[$primary_zone_name])) {
                continue;
            }

            // Set the timezone to appear under its primary location
            $tz_data[$primary_zone_name][] = $timezone;
        }

        // Sort each section by UTC offset
        foreach ($tz_data as $zone => $data) {
            $this->insertionSort($tz_data[$zone], 'offset');
        }

        return $tz_data;
    }

    /**
     * Constructs the timezone meta data using the given timezone and its identifier
     *
     * @param arary $zone_info An array of timezone information for the given identifier including:
     *  - ts Current time stamp
     *  - time Date/Time
     *  - offset The UTC offset in seconds
     *  - isdst Whether or this timezone is observing daylight savings (true/false)
     *  - abbr The abbreviation for this timezone
     * @param string $identifier The timezone identifier
     * @return An array of timezone meta data including:
     *  - id The timezone identifier
     *  - name The locale name
     *  - offset The offset from UTC in seconds
     *  - utc A string containg the HH::MM UTC offset
     *  - zone An array of zone names
     */
    private function timezoneFromIdentifier(&$zone_info, $identifier)
    {
        $zone = explode('/', $identifier, 2);

        $offset = isset($zone_info['offset']) ? $zone_info['offset'] : 0; // offset

        $offset_h = str_pad(abs((int)($offset/3600)), 2, '0', STR_PAD_LEFT); // offset in hours
        $offset_h = (($offset < 0 ? true : false) ? '-' : '+') . $offset_h;
        $offset_m = str_pad(abs((int)(($offset/60)%60)), 2, '0', STR_PAD_LEFT); // offset in mins

        $timezone = array(
            'id' => $identifier,
            'name' => str_replace('_', ' ', isset($zone[1]) ? $zone[1] : $zone[0]),
            'offset' => (int)$offset,
            'utc' => $offset_h . ':' . $offset_m . (isset($zone_info['isdst']) && $zone_info['isdst'] ? ' DST' : ''),
            'zone' => $zone
        );

        return $timezone;
    }

    /**
     * Insertion sort algorithm for numerically indexed arrays with string indexed elements.
     * Will sort items in $array based on values in the $key index. Sorts arrays in place.
     *
     * @param array $array The array to sort
     * @param string $key The index to sort on
     */
    private static function insertionSort(&$array, $key)
    {
        for ($i=1; $i<count($array); $i++) {
            self::insertSortInsert($array, $i, $array[$i], $key);
        }
    }

    /**
     * Insertion sort in inserter. Performs comparison and insertion for the given
     * element within the given array.
     *
     * @param array $array The array to sort
     * @param int $length The length to sort through
     * @param array $element The element of $array to insert somewhere in $array
     * @param string $key The index to compare
     */
    private static function insertSortInsert(&$array, $length, $element, $key)
    {
        $i = $length-1;
        for (; $i >= 0 && ($array[$i][$key] > $element[$key]); $i--) {
            $array[$i+1] = $array[$i];
        }
        $array[$i+1] = $element;
    }

    /**
     * Extends one array using another to overwrite existing values. Recursively merges
     * data.
     *
     * @param array $arr1 The array (default) to be merged into
     * @param array $arr2 The array to merge into $arr1
     * @return array The merged arrays
     */
    private function mergeArrays(array $arr1, array $arr2)
    {

        foreach ($arr2 as $key => $value) {
            if (array_key_exists($key, $arr1) && is_array($value)) {
                $arr1[$key] = $this->mergeArrays($arr1[$key], $arr2[$key]);
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }

    /**
     * Retrieves an instance of DateTime
     *
     * @param string|int $date The date or timestamp
     * @param DateTimeZone $timezone The timezone
     * @return DateTime
     */
    private function dateTime($date = null, DateTimeZone $timezone = null)
    {
        // Create the DateTime object from a string or Unix timestamp
        $timestamp = null;
        if (is_numeric($date)) {
            $timestamp = $date;
            $date = null;
        }

        $date_time = new DateTime($date, $timezone);

        // Set a Unix timestamp as the date
        if ($timestamp !== null) {
            $date_time->setTimestamp($timestamp);
        }

        return $date_time;
    }

    /**
     * Retrieves an istance of DateTimeZone
     *
     * @param string $timezone The timezone identifier
     * @return DateTimeZone
     */
    private function dateTimeZone($timezone)
    {
        return new DateTimeZone($timezone);
    }
}
