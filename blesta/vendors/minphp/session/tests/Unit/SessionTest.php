<?php
namespace Minphp\Session\Tests\Unit\Session;

use Minphp\Session\Session;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Minphp\Session\Session
 */
class SessionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::hasStarted
     *
     * @runInSeparateProcess
     */
    public function testConstruct()
    {
        $session = new Session();
        $this->assertInstanceOf('\Minphp\Session\Session', $session);
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::hasStarted
     * @covers ::start
     *
     * @runInSeparateProcess
     */
    public function testConstructWithOptions()
    {
        // Ensure the session is closed before beginning this test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $options = [
            'name' => 'my-session-name'
        ];
        $session = new Session(null, $options);

        $this->assertInstanceOf('\Minphp\Session\Session', $session);

        $session->start();
        $this->assertEquals($options['name'], ini_get('session.name'));
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::start
     * @covers ::hasStarted
     * @covers ::save
     *
     * @runInSeparateProcess
     */
    public function testStart()
    {
        $session = new Session();

        $this->assertTrue($session->start());
        $this->assertTrue($session->hasStarted());

        // Close the session
        $session->save();

        $this->assertFalse($session->hasStarted());
    }

    /**
     * @covers ::__construct
     * @covers ::hasSentHeaders
     * @covers ::start
     * @covers ::hasStarted
     * @covers ::save
     *
     * // Do not run this test in a separate process so that the headers are already sent
     */
    public function testStartHeadersSent()
    {
        $session = new Session();

        // Close the open session
        $session->save();

        // The headers are already sent since this test is not run in a separate process
        $this->assertFalse($session->start());
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::save
     * @covers ::hasStarted
     *
     * @runInSeparateProcess
     */
    public function testSave()
    {
        $session = new Session();
        $session->save();

        $this->assertFalse($session->hasStarted());
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::start
     * @covers ::hasStarted
     * @expectedException \LogicException
     *
     * @runInSeparateProcess
     */
    public function testSetOptionsException()
    {
        // Start the session
        $session = new Session();
        $session->start();

        // Setting options after the session has been started throws an exception
        $options = [
            'name' => 'my-session-name'
        ];
        $session->setOptions($options);
    }

    /**
     * @covers ::__construct
     * @covers ::hasSentHeaders
     * @covers ::save
     * @covers ::hasStarted
     * @covers ::setOptions
     * @expectedException \LogicException
     *
     * // Do not run this test in a separate process so that the headers are already sent
     */
    public function testSetOptionsHeadersSentException()
    {
        // Start the session
        $session = new Session();

        // Make sure the session is closed first
        $session->save();

        // Set the options, which will fail due to the exception
        $session->setOptions(['name' => 'abc123']);
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::start
     * @covers ::hasStarted
     * @covers ::regenerate
     * @covers ::save
     *
     * @runInSeparateProcess
     */
    public function testRegenerate()
    {
        $session = new Session();

        // Make sure the session is closed first
        $session->save();

        // Cannot regenerate with no active session
        $this->assertFalse($session->regenerate());

        $session->start();
        $lifetime = 100;
        $this->assertTrue($session->regenerate(false, $lifetime));
        $this->assertEquals($lifetime, ini_get('session.cookie_lifetime'));
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::getId
     * @covers ::setId
     * @covers ::start
     * @covers ::save
     * @covers ::hasStarted
     *
     * @runInSeparateProcess
     */
    public function testId()
    {
        $sessionId = 'sessionId';
        $session = new Session();

        // The default session ID should be available
        $session->start();
        $this->assertNotNull($session->getId());
        $session->save();

        // The session ID should change
        $session->setId($sessionId);
        $session->start();
        $this->assertEquals($sessionId, $session->getId());
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::getId
     * @covers ::setId
     * @covers ::hasStarted
     * @expectedException \LogicException
     *
     * @runInSeparateProcess
     */
    public function testIdException()
    {
        $session = new Session();
        $session->start();
        $session->setId('id-that-cant-be-set');
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::getName
     * @covers ::setName
     * @covers ::start
     * @covers ::save
     * @covers ::hasStarted
     *
     * @runInSeparateProcess
     */
    public function testName()
    {
        $sessionName = 'sessionName';
        $session = new Session();

        // The default session name should be available
        $session->start();
        $this->assertNotNull($session->getName());
        $session->save();

        // The session name should change
        $session->setName($sessionName);
        $session->start();
        $this->assertEquals($sessionName, $session->getName());
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::getName
     * @covers ::setName
     * @covers ::hasStarted
     * @expectedException \LogicException
     *
     * @runInSeparateProcess
     */
    public function testNameException()
    {
        $session = new Session();
        $session->start();
        $session->setName('name-that-cant-be-set');
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::read
     * @covers ::write
     * @covers ::start
     * @covers ::hasStarted
     *
     * @runInSeparateProcess
     */
    public function testReadWrite()
    {
        $key = 'value';
        $value = 'something';

        $session = new Session();
        $session->start();
        $this->assertEquals('', $session->read($key));
        $session->write($key, $value);
        $this->assertEquals($value, $session->read($key));
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::read
     * @covers ::write
     * @covers ::clear
     * @covers ::start
     * @covers ::hasStarted
     *
     * @runInSeparateProcess
     */
    public function testClear()
    {
        $session = new Session();
        $session->start();
        $session->write('key1', 'value1');
        $session->write('key2', 'value2');
        $session->write('key3', 'value3');

        $session->clear('key1');

        $this->assertArrayNotHasKey('key1', $_SESSION);
        $this->assertArrayHasKey('key2', $_SESSION);

        $session->clear();
        $this->assertEmpty($_SESSION);
    }

    /**
     * @covers ::__construct
     * @covers ::setOptions
     * @covers ::cookie
     */
    public function testCookie()
    {
        $this->markTestSkipped('Cannot test whether a cookie was created.');
    }
}
