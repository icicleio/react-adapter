<?php
namespace Icicle\Tests\ReactAdaptor\Loop;

use Icicle\ReactAdaptor\Loop\ReactTimer;
use Icicle\Tests\ReactAdaptor\TestCase;

class ReactTimerTest extends TestCase
{
    const TIMEOUT = 0.1;

    /**
     * @param   callable $callback
     * @param   float|int $interval
     * @param   bool $periodic
     *
     * @return \Icicle\ReactAdaptor\Loop\ReactTimer
     */
    public function createTimer($callback, $interval, $periodic = false)
    {
        $loop = $this->getMock('Icicle\ReactAdaptor\Loop\ReactLoop');

        $timer = $this->getMock('Icicle\Loop\Events\TimerInterface');

        $timer->method('getLoop')
            ->will($this->returnValue($loop));

        $timer->method('__invoke')
            ->will($this->returnCallback($callback));

        $timer->method('call')
            ->will($this->returnCallback($callback));

        $timer->method('getInterval')
            ->will($this->returnValue($interval));

        $timer->method('isPeriodic')
            ->will($this->returnValue($periodic));

        $pending = true;

        $timer->method('isPending')
            ->will($this->returnCallback(function () use (&$pending) {
                return $pending;
            }));

        $timer->method('stop')
            ->will($this->returnCallback(function () use (&$pending) {
                $pending = false;
            }));

        return new ReactTimer($loop, $timer);
    }

    public function testGetLoop()
    {
        $timer = $this->createTimer($this->createCallback(0), self::TIMEOUT);

        $this->assertInstanceOf('Icicle\ReactAdaptor\Loop\ReactLoop', $timer->getLoop());
    }

    public function testGetCallback()
    {
        $callback = $this->createCallback(1);

        $timer = $this->createTimer($callback, self::TIMEOUT);

        $callback = $timer->getCallback();
        $callback(); // Asserts that the callback is called, not that it is identical.
    }

    public function testGetInterval()
    {
        $interval = self::TIMEOUT;

        $timer = $this->createTimer($this->createCallback(0), $interval);

        $this->assertSame($interval, $timer->getInterval());
    }

    public function testIsPeriodic()
    {
        $timer = $this->createTimer($this->createCallback(0), self::TIMEOUT, false);

        $this->assertFalse($timer->isPeriodic());

        $timer = $this->createTimer($this->createCallback(0), self::TIMEOUT, true);

        $this->assertTrue($timer->isPeriodic());
    }

    public function testData()
    {
        $data = [1, 'test', 3.14159];

        $timer = $this->createTimer($this->createCallback(0), self::TIMEOUT);

        $timer->setData($data);

        $this->assertSame($data, $timer->getData());
    }

    public function testIsActive()
    {
        $timer = $this->createTimer($this->createCallback(0), self::TIMEOUT);

        $this->assertTrue($timer->isActive());
    }

    /**
     * @depends testIsActive
     */
    public function testCancel()
    {
        $timer = $this->createTimer($this->createCallback(0), self::TIMEOUT);

        $timer->cancel();

        $this->assertFalse($timer->isActive());
    }
}
