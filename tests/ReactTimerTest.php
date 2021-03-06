<?php
namespace Icicle\Tests\ReactAdapter;

use Icicle\Loop\Watcher\Timer;
use Icicle\ReactAdapter\ReactLoop;
use Icicle\ReactAdapter\ReactTimer;

class ReactTimerTest extends TestCase
{
    const TIMEOUT = 0.1;

    /**
     * @param callable $callback
     * @param float|int $interval
     * @param bool $periodic
     *
     * @return \Icicle\ReactAdapter\ReactTimer
     */
    public function createTimer($callback, $interval, $periodic = false)
    {
        $loop = $this->getMock(ReactLoop::class);

        $timer = $this->getMockBuilder(Timer::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->assertInstanceOf(ReactLoop::class, $timer->getLoop());
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
