<?php
namespace Blesta\Proration\Tests\Unit;

use Blesta\Proration\Proration;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Blesta\Proration\Proration
 */
class ProrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('Blesta\Proration\Proration', new Proration('2015-02-13T14:30:00-08:00', 1, 1, Proration::PERIOD_MONTH));
    }

    /**
     * @covers ::setTimeZone
     * @uses Blesta\Proration\Proration::__construct
     */
    public function testSetTimeZone()
    {
        $proration = new Proration('2015-02-25T00:00:00-08:00', 1, 1, Proration::PERIOD_MONTH);
        $this->assertInstanceOf('Blesta\Proration\Proration', $proration->setTimeZone('America/New_York'));
    }

    /**
     * @covers ::setProrateDate
     * @uses Blesta\Proration\Proration::__construct
     */
    public function testSetProrateDate()
    {
        $proration = new Proration('2015-02-25T00:00:00-08:00', 1, 1, Proration::PERIOD_MONTH);
        $this->assertInstanceOf('Blesta\Proration\Proration', $proration->setProrateDate('2015-03-01T00:00:00-08:00'));
    }

    /**
     * @covers ::startDate
     * @covers ::prorateDay
     * @covers ::term
     * @covers ::period
     * @uses Blesta\Proration\Proration::__construct
     */
    public function testGetters()
    {
        $start_date = '2015-02-25T00:00:00-08:00';
        $prorate_day = 10;
        $term = 1;
        $period = Proration::PERIOD_MONTH;
        $proration = new Proration($start_date, $prorate_day, $term, $period);

        $this->assertEquals($start_date, $proration->startDate());
        $this->assertEquals($prorate_day, $proration->prorateDay());
        $this->assertEquals($term, $proration->term());
        $this->assertEquals($period, $proration->period());
    }

    /**
     * @covers ::prorateDate
     * @covers ::prorateDateFromDay
     * @covers ::prorateDateFromDate
     * @uses Blesta\Proration\Proration::__construct
     * @uses Blesta\Proration\Proration::setTimeZone
     * @uses Blesta\Proration\Proration
     * @dataProvider prorateDateProvider
     */
    public function testProrateDate($start_date, $prorate_day, $term, $period, $time_zone, $prorate_date, $result)
    {
        $proration = new Proration($start_date, $prorate_day, $term, $period);
        $this->assertEquals(
            $result,
            $proration->setProrateDate($prorate_date)->setTimeZone($time_zone)->prorateDate()
        );
    }

    /**
     * @covers ::canProrate
     * @uses Blesta\Proration\Proration::__construct
     * @uses Blesta\Proration\Proration::setTimeZone
     * @uses Blesta\Proration\Proration
     * @dataProvider prorateDateProvider
     */
    public function testCanProrate($start_date, $prorate_day, $term, $period, $time_zone, $prorate_date, $result)
    {
        $proration = new Proration($start_date, $prorate_day, $term, $period);
        $this->assertEquals(
            $result !== null,
            $proration->setProrateDate($prorate_date)->setTimeZone($time_zone)->canProrate()
        );
    }

    /**
     * Data provider for testProrateDate
     *
     * @return array
     */
    public function prorateDateProvider()
    {
        return [
            // UTC to time zone
            [
                '2015-02-13T05:00:00-00:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                'America/New_York',
                null,
                '2015-03-01T00:00:00-05:00'
            ],
            // DST starts
            [
                '2015-03-01T01:00:00-08:00',
                8,
                1,
                Proration::PERIOD_MONTH,
                'America/Los_Angeles',
                null,
                '2015-03-08T00:00:00-08:00'
            ],
            [
                '2015-03-01T14:15:22-08:00',
                8,
                1,
                Proration::PERIOD_MONTH,
                'America/Los_Angeles',
                null,
                '2015-03-08T00:00:00-08:00'
            ],
            // DST ends
            [
                '2015-10-15T01:00:00-07:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                'America/Los_Angeles',
                null,
                '2015-11-01T00:00:00-07:00'
            ],
            [
                '2015-10-15T16:00:00-07:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                'America/Los_Angeles',
                null,
                '2015-11-01T00:00:00-07:00'
            ],
            [
                '2015-02-13T14:30:00-08:00',
                5,
                1,
                Proration::PERIOD_YEAR,
                'America/Los_Angeles',
                '2015-03-01T12:00:00-05:00',
                '2015-03-01T00:00:00-08:00'
            ],
            [
                '2015-02-13T14:30:00-08:00',
                1,
                1,
                Proration::PERIOD_YEAR,
                null,
                null,
                '2015-03-01T00:00:00-08:00'
            ],
            [
                '2015-02-13T14:30:00-08:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                '2015-03-01T00:00:00-08:00'
            ],
            [
                '2015-02-13T14:30:00-08:00',
                31,
                1,
                Proration::PERIOD_YEAR,
                null,
                null,
                '2015-02-28T00:00:00-08:00'
            ],
            [
                '2015-03-13T14:30:00-08:00',
                31,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                '2015-03-31T00:00:00-08:00'
            ],
            [
                '2015-02-24T14:30:00-08:00',
                26,
                1,
                Proration::PERIOD_YEAR,
                null,
                null,
                '2015-02-26T00:00:00-08:00'
            ],
            [
                '2015-01-31T00:00:00-08:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                '2015-02-01T00:00:00-08:00'
            ],
            [
                '2015-02-01T00:00:00-08:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                null
            ],
            [
                '2015-02-28T00:00:00-08:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                '2015-03-01T00:00:00-08:00'
            ],
            [
                '2016-02-29T00:00:00-08:00',
                1,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                '2016-03-01T00:00:00-08:00'
            ],
            [
                '2015-02-13T14:30:00-08:00',
                1,
                1,
                Proration::PERIOD_WEEK,
                null,
                null,
                null
            ],
            [
                '2015-02-13T14:30:00-08:00',
                1,
                1,
                Proration::PERIOD_DAY,
                null,
                null,
                null
            ],
            [
                '2015-02-13T14:30:00-08:00',
                1,
                1,
                Proration::PERIOD_ONETIME,
                null,
                null,
                null
            ],
            [
                '2015-02-13T14:30:00-08:00',
                0,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                null
            ],
            [
                '2015-02-13T14:30:00-08:00',
                -1,
                1,
                Proration::PERIOD_MONTH,
                null,
                null,
                null
            ]
        ];
    }

    /**
     * @covers ::prorateDays
     * @covers ::daysDiff
     * @uses Blesta\Proration\Proration
     * @uses Blesta\Proration\Proration::__construct
     * @dataProvider prorateDaysProvider
     */
    public function testProrateDays($start_date, $prorate_day, $term, $period, $result)
    {
        $proration = new Proration($start_date, $prorate_day, $term, $period);
        $this->assertEquals($result, $proration->prorateDays());
    }

    /**
     * Data provider for testProrateDays
     *
     * @return array
     */
    public function prorateDaysProvider()
    {
        return [
            ['2015-02-28T00:00:00-08:00', 1, 1, Proration::PERIOD_MONTH, 1],
            ['2015-02-28T11:59:59-08:00', 1, 1, Proration::PERIOD_MONTH, 1],
            ['2015-02-28T12:00:00-08:00', 1, 1, Proration::PERIOD_MONTH, 1],
            ['2015-02-28T12:00:01-08:00', 1, 1, Proration::PERIOD_MONTH, 0],
            ['2015-02-28T12:00:00-08:00', 2, 1, Proration::PERIOD_MONTH, 2],

            ['2015-01-31T12:00:00-08:00', 1, 1, Proration::PERIOD_MONTH, 1],
            ['2015-01-31T12:00:01-08:00', 1, 1, Proration::PERIOD_MONTH, 0],
            ['2015-01-31T11:59:59-08:00', 1, 1, Proration::PERIOD_YEAR, 1],
            ['2015-01-31T12:00:00-08:00', 1, 1, Proration::PERIOD_WEEK, 0],
            ['2015-01-31T12:00:00-08:00', 1, 1, Proration::PERIOD_DAY, 0],
            ['2015-01-31T12:00:00-08:00', 1, 1, Proration::PERIOD_ONETIME, 0]
        ];
    }

    /**
     * @covers ::proratePrice
     * @covers ::daysDiff
     * @uses Blesta\Proration\Proration
     * @uses Blesta\Proration\Proration::__construct
     * @dataProvider proratePriceProvider
     */
    public function testProratePrice($start_date, $prorate_day, $term, $period, $price, $result)
    {
        $proration = new Proration($start_date, $prorate_day, $term, $period);
        $this->assertEquals($result, $proration->proratePrice($price));
    }

    /**
     * Data provider for testProratePrice
     *
     * @return array
     */
    public function proratePriceProvider()
    {
        return [
            // 1 day of proration over 31 days ~= 0.0322580 * 100 = 3.2258
            ['2015-01-31T12:00:00-08:00', 1, 1, Proration::PERIOD_MONTH, 100.0, 3.2258],
            // 30 days of proration over 31 days ~= 0.9677418 * 100 = 96.7742
            ['2015-01-02T12:00:00-08:00', 1, 1, Proration::PERIOD_MONTH, 100.0, 96.7742],
            // 29 days of proration over 31 days ~= 0.9354838 * 100 = 93.5484
            ['2015-01-02T12:00:01-08:00', 1, 1, Proration::PERIOD_MONTH, 100.0, 93.5484],
            // Bad period
            ['2015-01-02T12:00:01-08:00', 1, 1, Proration::PERIOD_DAY, 100.0, 0.0],
            ['2015-01-02T12:00:01-08:00', 1, 1, Proration::PERIOD_WEEK, 100.0, 0.0],
            ['2015-01-02T12:00:01-08:00', 1, 1, Proration::PERIOD_ONETIME, 100.0, 0.0],
            // Bad date value
            [0, 1, 1, Proration::PERIOD_MONTH, 100.0, 0.0]
        ];
    }

    /**
     * @covers ::setProratablePeriods
     * @covers ::proratePrice
     * @covers ::canProrate
     * @uses Blesta\Proration\Proration
     * @dataProvider proratablePeriodsProvider
     *
     * @param Proration $from_proration
     * @param float $from_price
     * @param Proration $to_proration
     * @param float $to_price
     * @param float $diff_price
     */
    public function testSetProratablePeriods(
        Proration $from_proration,
        $from_price,
        Proration $to_proration,
        $to_price,
        $diff_price
    ) {
        $all_periods = [
            Proration::PERIOD_DAY,
            Proration::PERIOD_WEEK,
            Proration::PERIOD_MONTH,
            Proration::PERIOD_YEAR,
            Proration::PERIOD_ONETIME
        ];

        $from_proration->setProratablePeriods($all_periods);
        $to_proration->setProratablePeriods($all_periods);

        $this->assertTrue($from_proration->canProrate());
        $this->assertTrue($to_proration->canProrate());

        $this->assertEquals(
            $diff_price,
            $to_proration->proratePrice($to_price) - $from_proration->proratePrice($from_price)
        );
    }

    /**
     * Provider for testSetProratablePeriods
     * @return array
     */
    public function proratablePeriodsProvider()
    {
        return [
            [
                new Proration('2015-02-25T08:00:00-00:00', 1, 1, Proration::PERIOD_MONTH),
                1.00,
                new Proration('2015-02-25T08:00:00-00:00', 1, 1, Proration::PERIOD_MONTH),
                25.01,
                3.4300
            ],
            [
                new Proration('2015-02-25T08:00:00-00:00', 1, 1, Proration::PERIOD_MONTH),
                25.01,
                new Proration('2015-02-25T08:00:00-00:00', 1, 3, Proration::PERIOD_YEAR),
                350.00,
                -2.2955
            ],
            [
                new Proration('2015-02-25T08:00:00-00:00', 26, 1, Proration::PERIOD_DAY),
                1.00,
                new Proration('2015-02-25T08:00:00-00:00', 26, 1, Proration::PERIOD_DAY),
                0.50,
                -0.50
            ],
            [
                new Proration('2015-02-25T08:00:00-00:00', 1, 2, Proration::PERIOD_DAY),
                0.75,
                new Proration('2015-02-25T08:00:00-00:00', 1, 1, Proration::PERIOD_MONTH),
                25.01,
                2.0729
            ]
        ];
    }
}
