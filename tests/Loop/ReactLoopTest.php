<?php
namespace Icicle\Tests\ReactAdapter\Loop;

use Icicle\Loop;
use Icicle\ReactAdapter\Loop\ReactLoop;
use Icicle\Tests\ReactAdapter\TestCase;

class ReactLoopTest extends TestCase
{
    const TIMEOUT = 0.1;
    const RUNTIME = 0.05;
    const WRITE_STRING = 'abcdefghijklmnopqrstuvwxyz';
    const CHUNK_SIZE = 8192;

    /**
     * @var \Icicle\ReactAdapter\Loop\ReactLoop;
     */
    protected $loop;

    public function setUp()
    {
        $this->loop = $this->createLoop();
    }

    public function tearDown()
    {
        Loop\clear();
    }

    /**
     * @return \Icicle\ReactAdapter\Loop\ReactLoop
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
        $this->assertRunTimeLessThan([$this->loop, 'run'], self::RUNTIME);
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
            ->with($this->isInstanceOf('React\EventLoop\Timer\TimerInterface'));

        $timer = $this->loop->addTimer(self::TIMEOUT, $callback);

        $this->assertInstanceOf('Icicle\ReactAdapter\Loop\ReactTimer', $timer);

        $this->assertTrue($timer->isActive());

        $this->assertRunTimeBetween([$this->loop, 'run'], self::TIMEOUT, self::TIMEOUT + self::RUNTIME);

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
            ->with($this->isInstanceOf('React\EventLoop\Timer\TimerInterface'));

        $timer = $this->loop->addPeriodicTimer(self::TIMEOUT, $callback);

        $this->assertInstanceOf('Icicle\ReactAdapter\Loop\ReactTimer', $timer);

        $this->loop->addTimer(self::TIMEOUT * 3 + self::RUNTIME, function () use ($timer) {
            $timer->cancel();
        });

        $this->assertRunTimeLessThan([$this->loop, 'run'], self::TIMEOUT * 3 + self::RUNTIME * 2);

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

        $this->assertRunTimeLessThan([$this->loop, 'run'], self::RUNTIME);
    }
}