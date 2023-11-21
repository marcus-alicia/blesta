<?php
namespace Minphp\Date\Tests;

use PHPUnit_Framework_TestCase;
use Minphp\Date\Date;

/**
 * @coversDefaultClass \Minphp\Date\Date
 */
class DateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @uses \Minphp\Date\Date::setTimezone
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\Minphp\Date\Date', $this->getDate());
    }

    /**
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @covers ::setTimezone
     */
    public function testSetTimezone()
    {
        $date = $this->getDate();

        $this->assertInstanceOf('\Minphp\Date\Date', $date->setTimezone('UTC', 'America/Los_Angeles'));
    }

    /**
     * @param array|null The formats to set
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setTimezone
     * @covers ::setFormats
     *
     * @dataProvider formatProvider
     */
    public function testSetFormats($formats)
    {
        $date = $this->getDate();

        $this->assertInstanceOf('\Minphp\Date\Date', $date->setFormats($formats));
    }

    /**
     * Data provider for ::testSetFormats
     *
     * @return array
     */
    public function formatProvider()
    {
        return array(
            array(null),
            array(array('day' => 'd'))
        );
    }

    /**
     * @param string $fromDate The date to convert
     * @param string $format The format of the converted date to fetch
     * @param string $fromTimezone The $fromDate's timezone
     * @param string $toTimezone The timezone to convert to
     * @param string $expected The expected result
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @covers ::cast
     * @covers ::format
     * @covers ::toTime
     * @covers ::setTimezone
     * @covers ::dateTime
     * @covers ::dateTimeZone
     *
     * @dataProvider castProvider
     */
    public function testCast($fromDate, $format, $fromTimezone, $toTimezone, $expected)
    {
        $date = $this->getDate();

        $date->setTimezone($fromTimezone, $toTimezone);

        $this->assertEquals($expected, $date->cast($fromDate, $format));
    }

    /**
     * Data provider for ::testCast
     *
     * @return array
     */
    public function castProvider()
    {
        return array(
            array('2016-05-12T00:00:00+00:00', 'Y-m-d', 'UTC', 'UTC', '2016-05-12'),
            array('2016-05-12T07:00:00+00:00', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-05-12 07:00:00'),
            array('2016-05-12T00:00:00+00:00', 'O', 'UTC', 'UTC', '+0000'),
            array('2016-05-12T00:00:00+00:00', 'Y-m-d', 'UTC', 'America/Los_Angeles', '2016-05-11'),
            array('2016-05-12T07:00:00+00:00', 'Y-m-d H:i:s', 'UTC', 'America/Los_Angeles', '2016-05-12 00:00:00'),
            array('2016-05-12T00:00:00+00:00', 'O', 'UTC', 'America/Los_Angeles', '-0700'),
            array('2016-05-12T00:00:00-07:00', 'Y-m-d', 'America/Los_Angeles', 'UTC', '2016-05-12'),
            array('2016-05-12T00:00:00-07:00', 'Y-m-d H:i:s', 'America/Los_Angeles', 'UTC', '2016-05-12 07:00:00'),
            array('2016-12-12T00:00:00-08:00', 'Y-m-d H:i:s', 'America/Los_Angeles', 'UTC', '2016-12-12 08:00:00'),
            array('2016-05-12 00:00:00', 'Y-m-d H:i:s', 'America/Los_Angeles', 'UTC', '2016-05-12 07:00:00'),
            array('2016-12-12 00:00:00', 'Y-m-d H:i:s', 'America/Los_Angeles', 'UTC', '2016-12-12 08:00:00'),
            array('2016-05-12 07:00:00', 'Y-m-d H:i:s', 'UTC', 'America/Los_Angeles', '2016-05-12 00:00:00'),
            array('2016-12-12 08:00:00', 'Y-m-d H:i:s', 'UTC', 'America/Los_Angeles', '2016-12-12 00:00:00'),
            array(null, 'Y', 'UTC', 'UTC', date('Y')),
            array('2016-12-12 05:00:00', 'Y-m-d H:i:s', null, null, '2016-12-12 05:00:00')
        );
    }

    /**
     * @param string $fromDate The date to convert
     * @param string $modifier The strtotime-compatible modifier (e.g. +1 month)
     * @param string $format The format of the converted date to fetch
     * @param string $fromTimezone The $fromDate's timezone
     * @param string $toTimezone The timezone to convert to
     * @param string $expected The expected result
     * @param string $relativeFromTz The relative from timezone of the $fromDate if it differs from $fromTimezone
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @covers ::modify
     * @covers ::cast
     * @covers ::format
     * @covers ::toTime
     * @covers ::setTimezone
     * @covers ::dateTime
     * @covers ::dateTimeZone
     *
     * @dataProvider modifyProvider
     */
    public function testModify(
        $fromDate,
        $modifier,
        $format,
        $fromTimezone,
        $toTimezone,
        $expected,
        $relativeFromTz = null
    ) {
        $date = $this->getDate();

        $date->setTimezone($fromTimezone, $toTimezone);

        $this->assertEquals($expected, $date->modify($fromDate, $modifier, $format, $relativeFromTz));
    }

    public function modifyProvider()
    {
        $tz = array(
            'la' => 'America/Los_Angeles',
            'paris' => 'Europe/Paris',
            'syd' => 'Australia/Sydney'
        );

        return array(
            array('2016-05-12T00:00:00+00:00', '+1 second', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-05-12 00:00:01'),
            array('2016-05-12T00:00:00+00:00', '+1 minute', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-05-12 00:01:00'),
            array('2016-05-12T00:00:00+00:00', '+1 hour', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-05-12 01:00:00'),
            array('2016-05-12T00:00:00+00:00', '+1 day', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-05-13 00:00:00'),
            array('2016-05-12T00:00:00+00:00', '+1 week', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-05-19 00:00:00'),
            array('2016-05-12T00:00:00+00:00', '+1 month', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-06-12 00:00:00'),
            array('2016-06-12T00:00:00+00:00', '+1 month', 'Y-m-d H:i:s', 'UTC', 'UTC', '2016-07-12 00:00:00'),
            array('2016-05-12T00:00:00+00:00', '+1 year', 'Y-m-d H:i:s', 'UTC', 'UTC', '2017-05-12 00:00:00'),
            array('2016-05-12 00:00:00', '+2 days -1 second', 'Y-m-d H:i:s', $tz['la'], 'UTC', '2016-05-14 06:59:59'),
            array(null, 'May 10, 2017 midnight', 'c', $tz['la'], 'UTC', '2017-05-10T07:00:00+00:00'),
            array(null, 'May 10, 2017 midnight', 'c', 'UTC', $tz['la'], '2017-05-09T17:00:00-07:00'),
            array('2016-05-12T00:00:00+00:00', 'yesterday', 'Y-m-d H:i:s', 'UTC', $tz['la'], '2016-05-10 17:00:00'),
            // Timezone in date takes precedence over the from_timezone of America/Los_Angeles
            array(
                '2016-05-12T00:00:00+00:00',
                '-7 day',
                'M d, Y H:i O',
                $tz['la'],
                $tz['la'],
                'May 04, 2016 17:00 -0700'
            ),
            // 1 month is 30 days, not next numerical month
            array('2016-01-31', '+1 month', 'Y-m-d H:i:s', 'UTC', $tz['la'], '2016-03-01 16:00:00'),

            // Convert between LA daylight savings
            array('2016-03-05T00:00:00-08:00', '+1 month', 'c', $tz['la'], $tz['la'], '2016-04-05T00:00:00-07:00'),
            array('2016-03-05T00:00:00-08:00', '+1 month', 'Y-m-d H:i:s', $tz['la'], $tz['la'], '2016-04-05 00:00:00'),
            array('2016-03-05 00:00:00', '+1 month', 'c', $tz['la'], $tz['la'], '2016-04-05T00:00:00-07:00'),
            array('2016-04-05T00:00:00-07:00', '-1 month', 'c', $tz['la'], $tz['la'], '2016-03-05T00:00:00-08:00'),
            array('2016-04-05T00:00:00-07:00', '-1 month', 'Y-m-d H:i:s', $tz['la'], $tz['la'], '2016-03-05 00:00:00'),
            array('2016-04-05 00:00:00', '-1 month', 'c', $tz['la'], $tz['la'], '2016-03-05T00:00:00-08:00'),

            array('2016-10-15T00:00:00-07:00', '+1 month', 'c', $tz['la'], $tz['la'], '2016-11-15T00:00:00-08:00'),
            array('2016-10-15T00:00:00-07:00', '+1 month', 'Y-m-d H:i:s', $tz['la'], $tz['la'], '2016-11-15 00:00:00'),
            array('2016-10-15 00:00:00', '+1 month', 'c', $tz['la'], $tz['la'], '2016-11-15T00:00:00-08:00'),
            array('2016-11-15T00:00:00-08:00', '-1 month', 'c', $tz['la'], $tz['la'], '2016-10-15T00:00:00-07:00'),
            array('2016-11-15T00:00:00-08:00', '-1 month', 'Y-m-d H:i:s', $tz['la'], $tz['la'], '2016-10-15 00:00:00'),
            array('2016-11-15 00:00:00', '-1 month', 'c', $tz['la'], $tz['la'], '2016-10-15T00:00:00-07:00'),

            // Convert between LA and UTC across daylight savings
            array('2016-03-05T00:00:00-08:00', '+1 month', 'c', $tz['la'], 'UTC', '2016-04-05T07:00:00+00:00'),
            array('2016-03-05T00:00:00-08:00', '+1 month', 'Y-m-d H:i:s', $tz['la'], 'UTC', '2016-04-05 07:00:00'),
            array('2016-03-05 00:00:00', '+1 month', 'c', $tz['la'], 'UTC', '2016-04-05T07:00:00+00:00'),
            array('2016-04-05T00:00:00-07:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-03-05T08:00:00+00:00'),
            array('2016-04-05T00:00:00-07:00', '-1 month', 'Y-m-d H:i:s', $tz['la'], 'UTC', '2016-03-05 08:00:00'),
            array('2016-04-05 00:00:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-03-05T08:00:00+00:00'),

            array('2016-10-15T00:00:00-07:00', '+1 month', 'c', $tz['la'], 'UTC', '2016-11-15T08:00:00+00:00'),
            array('2016-10-15T00:00:00-07:00', '+1 month', 'Y-m-d H:i:s', $tz['la'], 'UTC', '2016-11-15 08:00:00'),
            array('2016-10-15 00:00:00', '+1 month', 'c', $tz['la'], 'UTC', '2016-11-15T08:00:00+00:00'),
            array('2016-11-15T00:00:00-08:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-10-15T07:00:00+00:00'),
            array('2016-11-15T00:00:00-08:00', '-1 month', 'Y-m-d H:i:s', $tz['la'], 'UTC', '2016-10-15 07:00:00'),
            array('2016-11-15 00:00:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-10-15T07:00:00+00:00'),

            // Convert between UTC and LA across daylight savings
            array('2016-03-05T00:00:00+00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-04-04T17:00:00-07:00'),
            array('2016-03-05T00:00:00+00:00', '+1 month', 'Y-m-d H:i:s', 'UTC', $tz['la'], '2016-04-04 17:00:00'),
            array('2016-03-05 00:00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-04-04T17:00:00-07:00'),
            array('2016-04-05T00:00:00+00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-03-04T16:00:00-08:00'),
            array('2016-04-05T00:00:00+00:00', '-1 month', 'Y-m-d H:i:s', 'UTC', $tz['la'], '2016-03-04 16:00:00'),
            array('2016-04-05 00:00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-03-04T16:00:00-08:00'),

            array('2016-10-15T00:00:00+00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-11-14T16:00:00-08:00'),
            array('2016-10-15T00:00:00+00:00', '+1 month', 'Y-m-d H:i:s', 'UTC', $tz['la'], '2016-11-14 16:00:00'),
            array('2016-10-15 00:00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-11-14T16:00:00-08:00'),
            array('2016-11-15T00:00:00+00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-10-14T17:00:00-07:00'),
            array('2016-11-15T00:00:00+00:00', '-1 month', 'Y-m-d H:i:s', 'UTC', $tz['la'], '2016-10-14 17:00:00'),
            array('2016-11-15 00:00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-10-14T17:00:00-07:00'),

            // Convert between LA and Sydney across daylight savings for both
            array('2016-03-05T00:00:00-08:00', '+2 months', 'c', $tz['la'], $tz['syd'], '2016-05-05T17:00:00+10:00'),
            array(
                '2016-03-05T00:00:00-08:00',
                '+2 months',
                'Y-m-d H:i:s',
                $tz['la'],
                $tz['syd'],
                '2016-05-05 17:00:00'
            ),
            array('2016-03-05 00:00:00', '+2 months', 'c', $tz['la'], $tz['syd'], '2016-05-05T17:00:00+10:00'),
            array('2016-05-05T00:00:00-07:00', '-2 months', 'c', $tz['la'], $tz['syd'], '2016-03-05T19:00:00+11:00'),
            array(
                '2016-05-05T00:00:00-07:00',
                '-2 months',
                'Y-m-d H:i:s',
                $tz['la'],
                $tz['syd'],
                '2016-03-05 19:00:00'
            ),
            array('2016-05-05 00:00:00', '-2 months', 'c', $tz['la'], $tz['syd'], '2016-03-05T19:00:00+11:00'),

            array('2016-09-15T00:00:00-07:00', '+2 months', 'c', $tz['la'], $tz['syd'], '2016-11-15T19:00:00+11:00'),
            array(
                '2016-09-15T00:00:00-07:00',
                '+2 months',
                'Y-m-d H:i:s',
                $tz['la'],
                $tz['syd'],
                '2016-11-15 19:00:00'
            ),
            array('2016-09-15 00:00:00', '+2 months', 'c', $tz['la'], $tz['syd'], '2016-11-15T19:00:00+11:00'),
            array('2016-11-15T00:00:00-08:00', '-2 months', 'c', $tz['la'], $tz['syd'], '2016-09-15T17:00:00+10:00'),
            array(
                '2016-11-15T00:00:00-08:00',
                '-2 months',
                'Y-m-d H:i:s',
                $tz['la'],
                $tz['syd'],
                '2016-09-15 17:00:00'
            ),
            array('2016-11-15 00:00:00', '-2 months', 'c', $tz['la'], $tz['syd'], '2016-09-15T17:00:00+10:00'),

            // Convert between Sydney and LA across daylight savings for both
            array('2016-03-05T00:00:00+11:00', '+2 months', 'c', $tz['syd'], $tz['la'], '2016-05-04T07:00:00-07:00'),
            array(
                '2016-03-05T00:00:00+11:00',
                '+2 months',
                'Y-m-d H:i:s',
                $tz['syd'],
                $tz['la'],
                '2016-05-04 07:00:00'
            ),
            array('2016-03-05 00:00:00', '+2 months', 'c', $tz['syd'], $tz['la'], '2016-05-04T07:00:00-07:00'),
            array('2016-05-05T00:00:00+10:00', '-2 months', 'c', $tz['syd'], $tz['la'], '2016-03-04T05:00:00-08:00'),
            array(
                '2016-05-05T00:00:00+10:00',
                '-2 months',
                'Y-m-d H:i:s',
                $tz['syd'],
                $tz['la'],
                '2016-03-04 05:00:00'
            ),
            array('2016-05-05 00:00:00', '-2 months', 'c', $tz['syd'], $tz['la'], '2016-03-04T05:00:00-08:00'),

            array('2016-09-15T00:00:00+10:00', '+2 months', 'c', $tz['syd'], $tz['la'], '2016-11-14T05:00:00-08:00'),
            array(
                '2016-09-15T00:00:00+10:00',
                '+2 months',
                'Y-m-d H:i:s',
                $tz['syd'],
                $tz['la'],
                '2016-11-14 05:00:00'
            ),
            array('2016-09-15 00:00:00', '+2 months', 'c', $tz['syd'], $tz['la'], '2016-11-14T05:00:00-08:00'),
            array('2016-11-15T00:00:00+11:00', '-2 months', 'c', $tz['syd'], $tz['la'], '2016-09-14T07:00:00-07:00'),
            array(
                '2016-11-15T00:00:00+11:00',
                '-2 months',
                'Y-m-d H:i:s',
                $tz['syd'],
                $tz['la'],
                '2016-09-14 07:00:00'
            ),
            array('2016-11-15 00:00:00', '-2 months', 'c', $tz['syd'], $tz['la'], '2016-09-14T07:00:00-07:00'),

            // Convert between LA and Sydney across daylight savings for LA only
            array('2016-02-15T00:00:00-08:00', '+1 month', 'c', $tz['la'], $tz['syd'], '2016-03-15T18:00:00+11:00'),
            array('2016-02-15T00:00:00-08:00', '+1 month', 'Y-m-d H:i:s', $tz['la'], $tz['syd'], '2016-03-15 18:00:00'),
            array('2016-02-15 00:00:00', '+1 month', 'c', $tz['la'], $tz['syd'], '2016-03-15T18:00:00+11:00'),
            array('2016-03-15T00:00:00-07:00', '-1 month', 'c', $tz['la'], $tz['syd'], '2016-02-15T19:00:00+11:00'),
            array('2016-03-15T00:00:00-07:00', '-1 month', 'Y-m-d H:i:s', $tz['la'], $tz['syd'], '2016-02-15 19:00:00'),
            array('2016-03-15 00:00:00', '-1 month', 'c', $tz['la'], $tz['syd'], '2016-02-15T19:00:00+11:00'),

            array('2016-10-15T00:00:00-07:00', '+1 month', 'c', $tz['la'], $tz['syd'], '2016-11-15T19:00:00+11:00'),
            array('2016-10-15T00:00:00-07:00', '+1 month', 'Y-m-d H:i:s', $tz['la'], $tz['syd'], '2016-11-15 19:00:00'),
            array('2016-10-15 00:00:00', '+1 month', 'c', $tz['la'], $tz['syd'], '2016-11-15T19:00:00+11:00'),
            array('2016-11-15T00:00:00-08:00', '-1 month', 'c', $tz['la'], $tz['syd'], '2016-10-15T18:00:00+11:00'),
            array('2016-11-15T00:00:00-08:00', '-1 month', 'Y-m-d H:i:s', $tz['la'], $tz['syd'], '2016-10-15 18:00:00'),
            array('2016-11-15 00:00:00', '-1 month', 'c', $tz['la'], $tz['syd'], '2016-10-15T18:00:00+11:00'),

            // Convert across timezones and ensure the reverse is true
            array('2016-01-15T00:00:00-08:00', '+1 month', 'c', $tz['la'], $tz['paris'], '2016-02-15T09:00:00+01:00'),
            array('2016-02-15T09:00:00+01:00', '-1 month', 'c', $tz['paris'], $tz['la'], '2016-01-15T00:00:00-08:00'),
            array('2016-01-15T00:00:00+00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-02-14T16:00:00-08:00'),
            array('2016-02-14T16:00:00-08:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-01-15T00:00:00+00:00'),
            array('2016-01-15T00:00:00+11:00', '+1 month', 'c', $tz['syd'], $tz['paris'], '2016-02-14T14:00:00+01:00'),
            array('2016-02-14T14:00:00+01:00', '-1 month', 'c', $tz['paris'], $tz['syd'], '2016-01-15T00:00:00+11:00'),

            // Convert across timezones and daylight savings and ensure the reverse is true
            array('2016-02-15T00:00:00-08:00', '+2 months', 'c', $tz['la'], $tz['paris'], '2016-04-15T09:00:00+02:00'),
            array('2016-04-15T09:00:00+02:00', '-2 months', 'c', $tz['paris'], $tz['la'], '2016-02-15T00:00:00-08:00'),
            array('2016-02-15T00:00:00+00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-03-14T17:00:00-07:00'),
            // Note the reverse has an hour difference due to DST
            array('2016-03-14T17:00:00-07:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-02-15T01:00:00+00:00'),
            array('2016-03-15T00:00:00+11:00', '+1 month', 'c', $tz['syd'], $tz['paris'], '2016-04-14T16:00:00+02:00'),
            // Note the reverse has a two hour difference because the timezones' DST are in opposite directions
            array('2016-04-14T16:00:00+02:00', '-1 month', 'c', $tz['paris'], $tz['syd'], '2016-03-15T02:00:00+11:00'),

            // Convert across timezones and daylight savings and ensure the reverse is true
            // BUT pass a relative from timezone to clear up the offset (maintaining time-of-day)
            // resulting from crossing DST
            array(
                '2016-02-15T00:00:00-08:00',
                '+2 months',
                'c',
                $tz['la'],
                $tz['paris'],
                '2016-04-15T09:00:00+02:00',
                $tz['paris']
            ),
            array(
                '2016-04-15T09:00:00+02:00',
                '-2 months',
                'c',
                $tz['paris'],
                $tz['la'],
                '2016-02-15T00:00:00-08:00',
                $tz['la']
            ),
            array(
                '2016-02-15T00:00:00+00:00',
                '+1 month',
                'c',
                'UTC',
                $tz['la'],
                '2016-03-14T16:00:00-07:00',
                $tz['la']
            ),
            array('2016-03-14T17:00:00-07:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-02-15T00:00:00+00:00', 'UTC'),
            array(
                '2016-03-15T00:00:00+11:00',
                '+1 month',
                'c',
                $tz['syd'],
                $tz['paris'],
                '2016-04-14T14:00:00+02:00',
                $tz['paris']
            ),
            array(
                '2016-04-14T16:00:00+02:00',
                '-1 month',
                'c',
                $tz['paris'],
                $tz['syd'],
                '2016-03-15T00:00:00+11:00',
                $tz['syd']
            ),

            // Convert between dates where the given timezone does not represent the offset of the date given
            // Note the timezone crossed DST and is off an hour from the original because of the offset
            array('2016-03-15T00:00:00+00:00', '+1 month', 'c', $tz['syd'], $tz['syd'], '2016-04-15T11:00:00+10:00'),
            array('2016-04-15T00:00:00+00:00', '-1 month', 'c', $tz['syd'], $tz['syd'], '2016-03-15T10:00:00+11:00'),

            array('2016-03-05T00:00:00+00:00', '+1 month', 'c', $tz['la'], $tz['la'], '2016-04-04T16:00:00-07:00'),
            array('2016-04-05T00:00:00+00:00', '-1 month', 'c', $tz['la'], $tz['la'], '2016-03-04T17:00:00-08:00'),

            array('2016-03-05T00:00:00+00:00', '+1 month', 'c', $tz['la'], 'UTC', '2016-04-04T23:00:00+00:00'),
            array('2016-04-05T00:00:00+00:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-03-05T01:00:00+00:00'),

            // Convert between dates where the given timezone does not represent the offset of the date given
            // BUT we pass a relative from timezone to clear things up
            array(
                '2016-03-15T00:00:00+00:00',
                '+1 month',
                'c',
                $tz['syd'],
                $tz['syd'],
                '2016-04-15T10:00:00+10:00',
                'UTC'
            ),
            array(
                '2016-04-15T00:00:00+00:00',
                '-1 month',
                'c',
                $tz['syd'],
                $tz['syd'],
                '2016-03-15T11:00:00+11:00',
                'UTC'
            ),

            array(
                '2016-03-05T00:00:00+00:00',
                '+1 month',
                'c',
                $tz['la'],
                $tz['la'],
                '2016-04-04T17:00:00-07:00',
                'UTC'
            ),
            array(
                '2016-04-05T00:00:00+00:00',
                '-1 month',
                'c',
                $tz['la'],
                $tz['la'],
                '2016-03-04T16:00:00-08:00',
                'UTC'
            ),

            array('2016-03-05T00:00:00+00:00', '+1 month', 'c', $tz['la'], 'UTC', '2016-04-05T00:00:00+00:00', 'UTC'),
            array('2016-04-05T00:00:00+00:00', '-1 month', 'c', $tz['la'], 'UTC', '2016-03-05T00:00:00+00:00', 'UTC'),

            // Convert between dates without timezone offsets where the given timezone does not represent the offset
            // of the date given BUT we set a relative from timezone to clear things up
            array('2016-03-15 00:00:00', '+1 month', 'c', $tz['syd'], $tz['syd'], '2016-04-15T00:00:00+10:00'),
            array('2016-04-15 00:00:00', '-1 month', 'c', $tz['syd'], $tz['syd'], '2016-03-15T00:00:00+11:00'),
            array('2016-03-15 00:00:00', '+1 month', 'c', $tz['syd'], $tz['syd'], '2016-04-15T00:00:00+10:00'),
            array('2016-04-15 00:00:00', '-1 month', 'c', $tz['syd'], $tz['syd'], '2016-03-15T00:00:00+11:00'),
            // Note that the date is off by the DST difference when modified if the original date represents a pre-DST
            // timestamp that contains that offset unless that old timezone is passed in
            array('2016-03-14 13:00:00', '+1 month', 'c', 'UTC', $tz['syd'], '2016-04-14T23:00:00+10:00'),
            array('2016-04-14 14:00:00', '-1 month', 'c', 'UTC', $tz['syd'], '2016-03-15T01:00:00+11:00'),
            array('2016-03-14 13:00:00', '+1 month', 'c', 'UTC', $tz['syd'], '2016-04-15T00:00:00+10:00', $tz['syd']),
            array('2016-04-14 14:00:00', '-1 month', 'c', 'UTC', $tz['syd'], '2016-03-15T00:00:00+11:00', $tz['syd']),

            array('2016-03-05 00:00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-04-04T17:00:00-07:00'),
            array('2016-04-05 00:00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-03-04T16:00:00-08:00'),
            // Note that the date is off by the DST difference when modified if the original date represents a pre-DST
            // timestamp that contains that offset unless that old timezone is passed in
            array('2016-03-05 08:00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-04-05T01:00:00-07:00'),
            array('2016-04-05 07:00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-03-04T23:00:00-08:00'),
            array('2016-03-05 08:00:00', '+1 month', 'c', 'UTC', $tz['la'], '2016-04-05T00:00:00-07:00', $tz['la']),
            array('2016-04-05 07:00:00', '-1 month', 'c', 'UTC', $tz['la'], '2016-03-05T00:00:00-08:00', $tz['la']),
        );
    }

    /**
     * @param string $startDate The start date
     * @param string $endDate The end date
     * @param array|null $formats The range formats
     * @param string $timezone The start timezone
     * @param array $expected The expected output
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @covers ::dateRange
     * @covers ::format
     * @covers ::toTime
     * @covers ::mergeArrays
     * @covers ::setTimezone
     * @covers ::dateTime
     * @covers ::dateTimeZone
     *
     * @dataProvider dateRangeProvider
     */
    public function testDateRange($startDate, $endDate, $formats, $timezone, $expected)
    {
        $date = $this->getDate();

        // Set the 'from' timezone
        $date->setTimezone($timezone);

        $this->assertEquals($expected, $date->dateRange($startDate, $endDate, $formats));
    }

    /**
     * Data Provider for ::testDateRange
     *
     * @return array
     */
    public function dateRangeProvider()
    {
        $formats = array(
            'start' => array(
                'same_day' => 'd',
                'same_month' => 'm| ',
                'same_year' => 'Y| ',
                'other' => 'Y-m-d| '
            )
        );

        return array(
            array('2016-03-01', '2017-03-01', null, null, 'March 1, 2016 - March 1, 2017'),
            array('2016-02-02', '2016-02-03', null, null, 'February 2-3, 2016'),
            array('2016-02-02', '2016-01-01', null, null, 'February 2 - January 1, 2016'),
            array('2016-02-02', '2016-02-02', null, null, 'February 2, 2016'),
            array('2016-03-01', '2017-03-01', $formats, null, '2016-03-01| March 1, 2017'),
            array('2016-02-02', '2016-02-03', $formats, null, '02| 3, 2016'),
            array('2016-02-02', '2016-01-01', $formats, null, '2016| January 1, 2016'),
            array('2016-02-02', '2016-02-02', $formats, null, '2'),
            array(1483228800, 1514764800, null, 'UTC', 'January 1, 2017 - January 1, 2018'),
            array(1483228800, '2017-01-01', null, 'UTC', 'January 1, 2017'),
            array(1483228800, '2017-01-03', null, 'UTC', 'January 1-3, 2017'),
            array(1483228800, '2017-02-02', null, 'UTC', 'January 1 - February 2, 2017'),
            array(1483228800, 1514764800, null, 'America/Los_Angeles', 'December 31, 2016 - December 31, 2017'),
            array(1483228800, '2017-01-01', null, 'America/Los_Angeles', 'December 31, 2016 - January 1, 2017'),
            array(1483228800, '2017-01-03', null, 'America/Los_Angeles', 'December 31, 2016 - January 3, 2017'),
            array(1483228800, '2017-02-02', null, 'America/Los_Angeles', 'December 31, 2016 - February 2, 2017'),
            array(
                1483228800,
                '2017-02-02T00:00:00-08:00',
                null,
                'America/Los_Angeles',
                'December 31, 2016 - February 2, 2017'
            )
        );
    }

    /**
     * @param string|int $dateTime The date to cast to time
     * @param int $expected Expected output
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @uses \Minphp\Date\Date::setTimezone
     * @covers ::toTime
     *
     * @dataProvider timeProvider
     */
    public function testToTime($dateTime, $expected)
    {
        $date = $this->getDate();

        $this->assertEquals($expected, $date->toTime($dateTime));
    }

    /**
     * Data Provider for ::testToTime
     *
     * @return array
     */
    public function timeProvider()
    {
        return array(
            array('2016-01-01T00:00:00-07:00', 1451631600),
            array('2016-01-01T00:00:00+00:00', 1451606400),
            array('2017-11-15T00:00:00+12:00', 1510660800),
            array(1, 1),
            array(100000, 100000)
        );
    }

    /**
     * @param int $start Start month
     * @param int $end End month
     * @param string $keyFormat Array key format
     * @param string $valueFormat Array value format
     * @param string $timezone The timezone
     * @param array $expected Expected result
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @covers ::getMonths
     * @covers ::setTimezone
     * @covers ::dateTime
     * @covers ::dateTimeZone
     *
     * @dataProvider monthsProvider
     */
    public function testGetMonths($start, $end, $keyFormat, $valueFormat, $timezone, array $expected)
    {
        $date = $this->getDate();

        // Set the 'from' timezone
        $date->setTimezone($timezone);

        $this->assertEquals($expected, $date->getMonths($start, $end, $keyFormat, $valueFormat));
    }

    /**
     * Data provider for ::testGetMonths
     *
     * @return array
     */
    public function monthsProvider()
    {
        return array(
            array(
                1, 12, 'm', 'n', null,
                array(
                    '01' => '1',
                    '02' => '2',
                    '03' => '3',
                    '04' => '4',
                    '05' => '5',
                    '06' => '6',
                    '07' => '7',
                    '08' => '8',
                    '09' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            array(
                1, 24, 'm', 'n', 'UTC',
                array(
                    '01' => '1',
                    '02' => '2',
                    '03' => '3',
                    '04' => '4',
                    '05' => '5',
                    '06' => '6',
                    '07' => '7',
                    '08' => '8',
                    '09' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            array(3, 3, 'n', 'n', null, array('3' => '3')),
            array(4, 3, 'n', 'n', 'UTC', array()),
            array(
                1, 12, 'm', 'n', 'America/Los_Angeles',
                array(
                    '01' => '1',
                    '02' => '2',
                    '03' => '3',
                    '04' => '4',
                    '05' => '5',
                    '06' => '6',
                    '07' => '7',
                    '08' => '8',
                    '09' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            )
        );
    }

    /**
     * @param int $start Start month
     * @param int $end End month
     * @param string $keyFormat Array key format
     * @param string $valueFormat Array value format
     * @param array $expected Expected result
     *
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @covers ::getYears
     * @covers ::setTimezone
     * @covers ::dateTime
     * @covers ::dateTimeZone
     *
     * @dataProvider yearsProvider
     */
    public function testGetYears($start, $end, $keyFormat, $valueFormat, $timezone, array $expected)
    {
        $date = $this->getDate();

        // Set the 'from' timezone
        $date->setTimezone($timezone);

        $this->assertEquals($expected, $date->getYears($start, $end, $keyFormat, $valueFormat));
    }

    /**
     * Data provider for ::testGetYears
     *
     * @return array
     */
    public function yearsProvider()
    {
        return array(
            array(
                2001, 2012, 'y', 'Y', null,
                array(
                    '01' => '2001',
                    '02' => '2002',
                    '03' => '2003',
                    '04' => '2004',
                    '05' => '2005',
                    '06' => '2006',
                    '07' => '2007',
                    '08' => '2008',
                    '09' => '2009',
                    '10' => '2010',
                    '11' => '2011',
                    '12' => '2012'
                )
            ),
            array(2003, 2003, 'Y', 'Y', null, array('2003' => '2003')),
            array(2004, 2003, 'y', 'y', 'UTC', array()),
            array(
                2001, 2012, 'y', 'Y', 'America/Los_Angeles',
                array(
                    '01' => '2001',
                    '02' => '2002',
                    '03' => '2003',
                    '04' => '2004',
                    '05' => '2005',
                    '06' => '2006',
                    '07' => '2007',
                    '08' => '2008',
                    '09' => '2009',
                    '10' => '2010',
                    '11' => '2011',
                    '12' => '2012'
                )
            ),
        );
    }

    /**
     * @covers ::__construct
     * @uses \Minphp\Date\Date::setFormats
     * @uses \Minphp\Date\Date::setTimezone
     * @covers ::getTimezones
     * @covers ::timezoneFromIdentifier
     * @covers ::insertSortInsert
     * @covers ::insertionSort
     * @covers ::dateTimeZone
     *
     * @dataProvider timezoneProvider
     */
    public function testGetTimezones($country)
    {
        $date = $this->getDate();
        $timezones = $date->getTimezones($country);

        // There should be timezones
        $this->assertNotEmpty($timezones);

        // A subset of the timezones should be given if a country is set
        if ($country) {
            $allTimezones = $date->getTimezones();
            $this->assertLessThan(count($allTimezones), count($timezones));
        }

        // Each timezone should consist of a set of keys
        $keys = array('id', 'name', 'offset', 'utc', 'zone');
        foreach ($timezones as $timezone) {
            foreach ($timezone as $data) {
                foreach ($keys as $key) {
                    $this->assertArrayHasKey($key, $data);
                }
            }
        }
    }

    /**
     * Data provider for ::testGetTimezones
     *
     * @return array
     */
    public function timezoneProvider()
    {
        return array(
            array(null),
            array('US'),
            array('')
        );
    }

    /**
     * Retrieves an instance of \Minphp\Date\Date
     *
     * @return \Minphp\Date\Date
     */
    private function getDate()
    {
        return new Date();
    }
}
