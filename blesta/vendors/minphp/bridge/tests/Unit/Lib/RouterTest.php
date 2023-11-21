<?php

/**
 * @coversDefaultClass \Router
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testGet()
    {
        $this->assertInstanceOf('Router', Router::get());
    }

    /**
     * @covers ::route
     * @expectedException \Exception
     */
    public function testRouteException()
    {
        Router::route('', '');
    }

    /**
     * @covers ::route
     * @covers ::match
     * @covers ::unescape
     * @dataProvider routingProvider
     */
    public function testRouting($uri, $route, $uris)
    {
        Router::route($uri, $route);

        foreach ($uris as $aUri => $expected) {
            $this->assertEquals($expected, Router::match($aUri));
        }
    }

    /**
     * Routing provider
     *
     * @return array
     */
    public function routingProvider()
    {
        return [
            ['foo/bar', 'bar/foo', ['foo/bar' => 'bar/foo']],
            ['foo/bar', 'bar/foo', ['foo/bar/baz' => 'bar/foo/baz']],
            ['([a-z]+)/([0-9]+)', 'something/$1/$2', ['abc/123/foo' => 'something/abc/123/foo']]
        ];
    }

    /**
     * @covers ::escape
     */
    public function testEscape()
    {
        $this->assertEquals(
            '\\/path\\/to\\/item',
            Router::escape('/path/to/item')
        );
    }

    /**
     * @covers ::unescape
     */
    public function testUnescape()
    {
        $this->assertEquals(
            '/path/to/item',
            Router::unescape('\\/path\\/to\\/item')
        );
    }

    /**
     * @covers ::makeURI
     */
    public function testMakeURI()
    {
        $this->assertEquals(
            '/path/to/file',
            Router::makeURI('\\path\\to\\file')
        );
    }

    /**
     * @covers ::parseURI
     */
    public function testParseURI()
    {
        $this->assertEquals(
            ['a', 'b', 'c', '', '?w=x&y=z'],
            Router::parseURI('a/b/c/?w=x&y=z')
        );
        $this->assertEquals(
            ['a', 'b', 'c', '?w=x&y=z'],
            Router::parseURI('a/b/c?w=x&y=z')
        );
    }

    /**
     * @covers ::filterURI
     * @covers ::setWebDir
     * @dataProvider filterURIProvider
     */
    public function testFilterURI($webdir, $uri, $expected)
    {
        Router::setWebDir($webdir);
        $this->assertEquals($expected, Router::filterURI($uri));
    }

    /**
     * Data provider for testFilterURI
     * @return array
     */
    public function filterURIProvider()
    {
        return [
            ['/', '/path/to/file', 'path/to/file'],
            ['/path', '/path/to/file', '/to/file'],
            ['/path/index.php', '/path/index.php/to/file', '/to/file']
        ];
    }

    /**
     * @covers ::isCallable
     */
    public function testIsCallable()
    {
        $controller = $this->getMockBuilder('Controller')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue(Router::isCallable($controller, 'index'));
        $this->assertFalse(Router::isCallable($controller, '__construct'));
        $this->assertFalse(Router::isCallable($controller, 'preAction'));
        $this->assertFalse(Router::isCallable($controller, 'nonexistentMethod'));
        $this->assertFalse(Router::isCallable(null, null));
    }

    /**
     * @covers ::routesTo
     * @covers ::setPluginDir
     * @covers ::setDefaultController
     * @dataProvider routesToProvider
     */
    public function testRoutesTo($uri, $parts)
    {
        $fixtureDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR;

        Router::setDefaultController('main');
        Router::setPluginDir($fixtureDir . 'plugins' . DIRECTORY_SEPARATOR);
        $result = Router::routesTo($uri);

        $this->assertArrayHasKey('plugin', $result);
        $this->assertArrayHasKey('controller', $result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('get', $result);
        $this->assertArrayHasKey('uri', $result);
        $this->assertArrayHasKey('uri_str', $result);
        //$this->assertEquals(rtrim($uri, '/') . '/', $result['uri_str']);

        foreach ($parts as $part => $value) {
            $this->assertEquals($value, $result[$part]);
        }
    }

    /**
     * Data provider for testRoutesTo
     */
    public function routesToProvider()
    {
        return array(
            [
                '/',
                [
                    'plugin' => null,
                    'controller' => 'main',
                    'action' => null,
                    'get' => [],
                    'uri' => [],
                    'uri_str' => '/'
                ]
            ],
            [
                'controller/action/?key1=value1&key2=value2',
                [
                    'plugin' => null,
                    'controller' => 'controller',
                    'action' => 'action',
                    'get' => ['key1' => 'value1', 'key2' => 'value2'],
                    'uri' => ['controller', 'action', '?key1=value1&key2=value2'],
                    'uri_str' => 'controller/action/?key1=value1&key2=value2'
                ]
            ],
            [
                'controller/action/get1/get2',
                [
                    'plugin' => null,
                    'controller' => 'controller',
                    'action' => 'action',
                    'get' => ['get1', 'get2'],
                    'uri' => ['controller', 'action', 'get1', 'get2'],
                    'uri_str' => 'controller/action/get1/get2/'
                ]
            ],
            [
                'controller/action/get1/get2?a=b&c=d',
                [
                    'plugin' => null,
                    'controller' => 'controller',
                    'action' => 'action',
                    'get' => ['get1', 'get2', 'a' => 'b', 'c' => 'd'],
                    'uri' => ['controller', 'action', 'get1', 'get2', '?a=b&c=d'],
                    'uri_str' => 'controller/action/get1/get2?a=b&c=d'
                ]
            ],
            [
                'controller/action/key1:value1/key2:value2',
                [
                    'plugin' => null,
                    'controller' => 'controller',
                    'action' => 'action',
                    'get' => ['key1' => 'value1', 'key2' => 'value2'],
                    'uri' => ['controller', 'action', 'key1:value1', 'key2:value2'],
                    'uri_str' => 'controller/action/key1:value1/key2:value2/'
                ]
            ],
            [
                'my_plugin/my_plugin_main_controller/index/get1/get2/',
                [
                    'plugin' => 'my_plugin',
                    'controller' => 'my_plugin_main_controller',
                    'action' => 'index',
                    'get' => ['get1', 'get2'],
                    'uri' => ['my_plugin', 'my_plugin_main_controller', 'index', 'get1', 'get2'],
                    'uri_str' => 'my_plugin/my_plugin_main_controller/index/get1/get2/'
                ]
            ],
            [
                'my_plugin/my_plugin_main_controller/index/get1/get2/?a=b',
                [
                    'plugin' => 'my_plugin',
                    'controller' => 'my_plugin_main_controller',
                    'action' => 'index',
                    'get' => ['get1', 'get2', 'a' => 'b'],
                    'uri' => ['my_plugin', 'my_plugin_main_controller', 'index', 'get1', 'get2', '?a=b'],
                    'uri_str' => 'my_plugin/my_plugin_main_controller/index/get1/get2/?a=b'
                ]
            ],
            [
                'my_plugin/my_plugin_main_controller/?a=b&c=d',
                [
                    'plugin' => 'my_plugin',
                    'controller' => 'my_plugin_main_controller',
                    'action' => null,
                    'get' => ['a' => 'b', 'c' => 'd'],
                    'uri' => ['my_plugin', 'my_plugin_main_controller', '?a=b&c=d'],
                    'uri_str' => 'my_plugin/my_plugin_main_controller/?a=b&c=d'
                ]
            ]
        );
    }
}
