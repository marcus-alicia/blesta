<?php

use Minphp\Container\Container;
use Minphp\Bridge\Initializer;

/**
 * @coversDefaultClass \Dispatcher
 */
class DispatcherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Set up
     */
    public function setUp()
    {
        $fixtureDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR;

        $init = Initializer::get();
        $container = new Container();


        $container->set('minphp.cache', function () {
            return ['enabled' => false];
        });
        $container->set('minphp.constants', function () use ($fixtureDir) {
            return [
                'ROOTWEBDIR' => $fixtureDir,
                'PLUGINDIR' => $fixtureDir . 'plugins' . DIRECTORY_SEPARATOR,
                'WEBDIR' => $fixtureDir,
                'APPDIR' => 'app' . DIRECTORY_SEPARATOR
            ];
        });
        $container->set('minphp.mvc', function () {
            return [
                'default_controller' => 'main',
                'default_structure' => 'structure',
                'default_view' => 'default',
                'view_extension' => '.pdt',
                'cli_render_views' => false,
                '404_forwarding' => false,
                'error_view' => 'errors'
            ];
        });
        $container->set('loader', function () {
            return Loader::get();
        });
        $container->set('view', $container->factory(function () {
            return new View();
        }));

        $init->setContainer($container);

        $this->dispatcher = new Dispatcher();
    }

    /**
     * @covers ::dispatchCli
     * @dataProvider dispatchCliProvider
     */
    public function testDispatchCli(array $args, $uriStr)
    {
        /*
        $routerMock = $this->getMockBuilder('\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $routerMock->expects($this->once())
            ->method('routesTo')
            ->with($uriStr);

        $this->dispatcher->setRouter($routerMock);
        $this->dispatcher->dispatchCli($args);
         *
         */
        $this->markTestIncomplete();
    }

    public function dispatchCliProvider()
    {
        return [
            [['index.php', 'a', 'b', 'c'], 'a/b/c/'],
            [['index.php', '-a', '--b', 'c'], '-a/--b/c/']
        ];
    }

    /**
     * @covers ::dispatch
     * @todo   Implement testDispatch().
     */
    public function testDispatch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ::raiseError
     * @dataProvider raiseErrorProvider
     */
    public function testRaiseError($e, $type)
    {
        switch ($type) {
            case 'output':
                $this->expectOutputRegex('//i', $e->getMessage());
                Dispatcher::raiseError($e);
                break;
            case 'header':
                Dispatcher::raiseError($e);
                $this->assertTrue(headers_sent());
                break;
            case 'exception':
                $exception = null;

                $initializer = Initializer::get();
                $initializer->getContainer()->remove('minphp.mvc');
                $initializer->getContainer()->set('minphp.mvc', function () {
                    return [
                        'default_controller' => 'main',
                        'default_structure' => 'structure',
                        'default_view' => 'default',
                        'view_extension' => '.pdt',
                        'cli_render_views' => false,
                        '404_forwarding' => false,
                        'error_view' => 'nonexistentview'
                    ];
                });

                try {
                    Dispatcher::raiseError($e);
                } catch (Throwable $thrown) {
                    $exception = $thrown;
                } catch (Exception $thrown) {
                    $exception = $thrown;
                }

                $this->assertSame($e, $exception);
                break;
        }
    }

    public function raiseErrorProvider()
    {
        return [
            [new UnknownException('test error', 1, null, null, 0), 'output'],
            [new Exception('404', 404), 'header'],
            [new Exception('error'), 'exception'],
        ];
    }
    /**
     * @covers ::stripSlashes
     */
    public function testStripSlashes()
    {
        $str = "I'm a clean string.";
        $escaped = addslashes($str);

        Dispatcher::stripSlashes($escaped);
        $this->assertEquals($str, $escaped);
    }
}
