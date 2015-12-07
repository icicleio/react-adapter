<?php
namespace Icicle\Tests\ReactAdapter;

use Icicle\Loop;
use Icicle\Loop\SelectLoop;
use Icicle\ReactAdapter\ReactLoop;
use Icicle\ReactAdapter\ReactTimer;
use React\EventLoop\Timer\TimerInterface;

class ReactLoopTest extends TestCase
{
    const TIMEOUT = 0.1;
    const WRITE_STRING = 'abcdefghijklmnopqrstuvwxyz';
    const CHUNK_SIZE = 8192;

    /**
     * @var \Icicle\ReactAdapter\ReactLoop;
     */
    protected $loop;

    public function setUp()
    {
        Loop\loop(new SelectLoop());
        $this->loop = $this->createLoop();
    }

    /**
     * @return \Icicle\ReactAdapter\ReactLoop
     */
    protected function createLoop()
    {
        return new ReactLoop();
    }

    /**
     * @return resource[]
     */
    public function createSockets()
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($sockets[1], self::WRITE_STRING); // Make $sockets[0] readable.

        return $sockets;
    }

    public function testNoBlockingOnEmptyLoop()
    {
        $this->assertRunTimeLessThan([$this->loop, 'run'], self::RUNTIME_PRECISION);
    }

    public function testNextTick()
    {
        $callback = $this->createCallback(3);

        $this->loop->nextTick($callback);
        $this->loop->nextTick($callback);

        $this->loop->tick();

        $this->loop->nextTick($callback);

        $this->loop->tick();
    }

    /**
     * @depends testNextTick
     */
    public function testNextTickWithinNextTick()
    {
        $callback = $this->createCallback(3);

        $this->loop->nextTick($callback);
        $this->loop->nextTick(function () use ($callback) {
            $this->loop->nextTick($callback);
            $this->loop->nextTick(function () use ($callback) {
                $this->loop->nextTick($callback);
            });
        });

        $this->loop->tick();
    }

    /**
     * @depends testNextTick
     */
    public function testFutureTick()
    {
        $callback = $this->createCallback(1);

        $this->loop->futureTick($callback);

        $this->loop->nextTick(function () use ($callback) {
            $this->loop->futureTick($callback);
        });

        $this->loop->tick();
    }

    public function testAddReadStream()
    {
        list($readable, $writable) = $this->createSockets();

        $callback = $this->createCallback(2);
        $callback->method('__invoke')
            ->with($this->identicalTo($readable), $this->identicalTo($this->loop));

        $this->loop->addReadStream($readable, $callback);

        $this->loop->tick();

        // Data not consumed, so ticking again should call function again.

        $this->loop->tick();
    }

    /**
     * @depends testAddReadStream
     */
    public function testReadFromStream()
    {
        list($readable, $writable) = $this->createSockets();

        $callback = function ($stream, $loop) {
            $data = fread($stream, self::CHUNK_SIZE);
            $this->assertSame(self::WRITE_STRING, $data);
        };

        $this->loop->addReadStream($readable, $callback);

        $this->loop->tick();
    }

    public function testRemoveReadStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addReadStream($readable, $this->createCallback(0));
        $this->loop->removeReadStream($readable);

        $this->loop->tick();
    }

    /**
     * @depends testAddReadStream
     */
    public function testReAddReadStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addReadStream($readable, $this->createCallback(0));
        $this->loop->addReadStream($readable, $this->createCallback(1));

        $this->loop->tick();

        $this->loop->addReadStream($readable, $this->createCallback(1));

        $this->loop->tick();
    }

    /**
     * @depends testRemoveReadStream
     */
    public function removeReadStreamWithinCallback()
    {
        list($readable, $writable) = $this->createSockets();

        $callback = $this->createCallback(1);

        $callback = function ($stream, $loop) use ($callback) {
            $callback();
            $this->loop->removeReadStream($stream);
        };

        $this->loop->addReadStream($readable, $callback);

        $this->loop->tick();

        $this->loop->tick();
    }

    /**
     * @depends testRemoveReadStream
     */
    public function testAddPreviouslyRemovedReadStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addReadStream($readable, $this->createCallback(0));
        $this->loop->removeReadStream($readable);

        $this->loop->tick();

        $this->loop->addReadStream($readable, $this->createCallback(1));

        $this->loop->tick();
    }

    public function testAddWriteStream()
    {
        list($readable, $writable) = $this->createSockets();

        $callback = $this->createCallback(2);
        $callback->method('__invoke')
            ->with($this->identicalTo($writable), $this->identicalTo($this->loop));

        $this->loop->addWriteStream($writable, $callback);

        $this->loop->tick();

        // Stream still writable, so ticking again should call function again.

        $this->loop->tick();
    }

    public function testRemoveWriteStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addWriteStream($writable, $this->createCallback(0));
        $this->loop->removeWriteStream($writable);

        $this->loop->tick();
    }

    /**
     * @depends testAddWriteStream
     */
    public function testReAddWriteStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addWriteStream($writable, $this->createCallback(0));
        $this->loop->addWriteStream($writable, $this->createCallback(1));

        $this->loop->tick();

        $this->loop->addWriteStream($writable, $this->createCallback(1));

        $this->loop->tick();
    }

    /**
     * @depends testRemoveWriteStream
     */
    public function removeWriteStreamWithinCallback()
    {
        list($readable, $writable) = $this->createSockets();

        $callback = $this->createCallback(1);

        $callback = function ($stream, $loop) use ($callback) {
            $callback();
            $this->loop->removeWriteStream($stream);
        };

        $this->loop->addWriteStream($writable, $callback);

        $this->loop->tick();

        $this->loop->tick();
    }

    /**
     * @depends testRemoveWriteStream
     */
    public function testAddPreviouslyRemovedWriteStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addWriteStream($writable, $this->createCallback(0));
        $this->loop->removeWriteStream($writable);

        $this->loop->tick();

        $this->loop->addWriteStream($writable, $this->createCallback(1));

        $this->loop->tick();
    }

    /**
     * @depends testAddReadStream
     * @depends testAddWriteStream
     */
    public function testRemoveStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addReadStream($readable, $this->createCallback(0));
        $this->loop->addWriteStream($writable, $this->createCallback(0));

        $this->loop->removeStream($readable);
        $this->loop->removeStream($writable);

        $this->loop->tick();
    }

    /**
     * @depends testRemoveStream
     */
    public function testPreviouslyRemovedStream()
    {
        list($readable, $writable) = $this->createSockets();

        $this->loop->addReadStream($readable, $this->createCallback(0));
        $this->loop->addWriteStream($writable, $this->createCallback(0));

        $this->loop->removeStream($readable);
        $this->loop->removeStream($writable);

        $this->loop->removeReadStream($readable);
        $this->loop->removeWriteStream($writable);

        $this->loop->removeStream($readable);
        $this->loop->removeStream($writable);

        $this->loop->tick();
    }

    public function testAddTimer()
    {
        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->isInstanceOf(TimerInterface::class));

        $timer = $this->loop->addTimer(self::TIMEOUT, $callback);

        $this->assertInstanceOf(ReactTimer::class, $timer);

        $this->assertTrue($timer->isActive());

        $this->assertRunTimeGreaterThan([$this->loop, 'run'], self::TIMEOUT - self::RUNTIME_PRECISION);

        $this->assertFalse($timer->isActive());
    }

    /**
     * @depends testAddTimer
     */
    public function testCancelTimer()
    {
        $timer = $this->loop->addTimer(self::TIMEOUT, $this->createCallback(0));

        $timer->cancel();

        $this->loop->tick();
    }

    /**
     * @depends testCancelTimer
     */
    public function testAddPeriodicTimer()
    {
        $callback = $this->createCallback(3);
        $callback->method('__invoke')
            ->with($this->isInstanceOf(TimerInterface::class));

        $timer = $this->loop->addPeriodicTimer(self::TIMEOUT, $callback);

        $this->assertInstanceOf(ReactTimer::class, $timer);

        $this->loop->addTimer(self::TIMEOUT * 3.5, function () use ($timer) {
            $timer->cancel();
        });

        $this->assertRunTimeLessThan([$this->loop, 'run'], self::TIMEOUT * 3 + self::RUNTIME_PRECISION);

        $this->assertFalse($timer->isActive());
    }

    /**
     * @depends testNextTick
     * @depends testAddTimer
     */
    public function testStop()
    {
        $this->loop->nextTick([$this->loop, 'stop']);

        $this->loop->addTimer(self::TIMEOUT, $this->createCallback(0));

        $this->assertRunTimeLessThan([$this->loop, 'run'], self::RUNTIME_PRECISION);
    }
}