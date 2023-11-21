<?php

/**
 * @coversDefaultClass \Loader
 */
class LoaderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $fixtureDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR;

        $appDir = $fixtureDir . 'app' . DIRECTORY_SEPARATOR;

        Loader::setDirectories([
            $appDir,
            'models' => $appDir . 'models' . DIRECTORY_SEPARATOR,
            'controllers' => $appDir . 'controllers' . DIRECTORY_SEPARATOR,
            'components' => $fixtureDir . 'components' . DIRECTORY_SEPARATOR,
            'helpers' => $fixtureDir . 'helpers' . DIRECTORY_SEPARATOR,
            'plugins' => $fixtureDir . 'plugins' . DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testGet()
    {
        $this->assertInstanceOf('\Loader', Loader::get());
    }

    /**
     * @covers ::autoload
     * @covers ::parseClassName
     * @covers ::fromCamelCase
     * @covers ::load
     * @covers ::setDirectories
     *
     * @dataProvider autoloadProvider
     */
    public function testAutoload($class, $plugin, $type)
    {
        $appendedClassName = $plugin !== null
            ? $plugin . '.' . $class
            : $class;

        $this->assertTrue(Loader::autoload($appendedClassName, $type));
        $this->assertTrue(class_exists($class, false));
    }

    /**
     * @covers ::autoload
     */
    public function testCantAutoloadNamespace()
    {
        $this->assertFalse(Loader::autoload('\\My\\Namespace\\Class'));
    }

    /**
     * @covers ::autoload
     */
    public function testCantFindClasstoAutoload()
    {
        $this->assertFalse(Loader::autoload('SomeClassNameThatDoesNotExist'));
    }

    /**
     * Data Provider for testAutoload
     *
     * @return array
     */
    public function autoloadProvider()
    {
        return [
            ['AppController', null, null],
            ['MyController', null, 'controllers'],
            ['MyModel', null, 'models'],
            ['MyComponent', null, 'components'],
            ['MyHelper', null, 'helpers'],
            ['MyPluginController', 'MyPlugin', null],
            ['MyPluginMainController', 'MyPlugin', 'controllers'],
            ['MyOtherPluginMainModel', 'MyOtherPlugin', 'models'],
            ['MyOtherPluginMainController', 'MyOtherPlugin', null],
            ['MyOtherPluginController', 'MyOtherPlugin', null],
        ];
    }

    /**
     * @covers ::loadModels
     * @covers ::loadInstances
     * @covers ::toCamelCase
     * @covers ::createInstance
     */
    public function testLoadModels()
    {
        $parent = new stdClass();
        $models = ['MyModel'];

        Loader::loadModels($parent, $models);
        foreach ($models as $model) {
            $this->assertInstanceOf("\\" . $model, $parent->{$model});
        }
    }

    /**
     * @covers ::loadModels
     * @covers ::loadInstances
     * @covers ::toCamelCase
     * @covers ::createInstance
     * @dataProvider pluginModelProvider
     */
    public function testLoadPluginModels($model, $plugin)
    {
        $parent = new stdClass();
        $models = [$plugin . '.' . $model];

        Loader::loadModels($parent, $models);
        $this->assertInstanceOf("\\" . $model, $parent->{$model});
    }

    /**
     * Data Provider for testLoadPluginModels
     *
     * @return array
     */
    public function pluginModelProvider()
    {
        return [
            ['MyOtherPluginMainModel', 'MyOtherPlugin']
        ];
    }

    /**
     * @covers ::loadComponents
     * @covers ::loadInstances
     * @covers ::toCamelCase
     * @covers ::createInstance
     */
    public function testLoadComponents()
    {
        $parent = new stdClass();
        $components = ['MyComponent', 'my_other_component' => ['paramA', 'paramB']];

        Loader::loadComponents($parent, $components);
        foreach ($components as $key => $value) {
            $component = $value;
            if (is_array($value)) {
                $component = $key;
                if ($component === 'my_other_component') {
                    $component = 'MyOtherComponent';
                }
            }
            $this->assertInstanceOf("\\" . $component, $parent->{$component});
        }
    }

    /**
     * @covers ::loadHelpers
     * @covers ::loadInstances
     * @covers ::toCamelCase
     * @covers ::createInstance
     * @uses View
     */
    public function testLoadHelpers()
    {
        $parent = new stdClass();
        $parent->view = $this->getMockBuilder('\View')
            ->disableOriginalConstructor()
            ->getMock();
        $parent->structure = $this->getMockBuilder('\View')
            ->disableOriginalConstructor()
            ->getMock();
        $helpers = ['MyHelper'];

        Loader::loadHelpers($parent, $helpers);
        foreach ($helpers as $helper) {
            $this->assertInstanceOf("\\" . $helper, $parent->{$helper});
            $this->assertInstanceOf("\\" . $helper, $parent->view->{$helper});
            $this->assertInstanceOf("\\" . $helper, $parent->structure->{$helper});
        }
    }
}
