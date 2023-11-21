<?php

use Minphp\Container\Container;
use Minphp\Bridge\Initializer;

/**
 * @coversDefaultClass \Configure
 */
class ConfigureTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::set
     * @covers ::updateContainerParam
     * @covers ::getInstance
     */
    public function testSetShouldUpdateContainer()
    {
        $session = ['db' => ['tbl' => 'sessions']];

        $init = Initializer::get();
        $container = new Container();
        $container->set(
            'minphp.session',
            function ($c) use ($session) {
                return $session;
            }
        );
        $init->setContainer($container);

        $this->assertEquals($session, $container->get('minphp.session'));

        $expected = 'other_sessions';

        Configure::set('Session.tbl', $expected);

        $this->assertEquals(
            $expected,
            $container->get('minphp.session')['db']['tbl']
        );
    }
}
