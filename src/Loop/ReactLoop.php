<?php
namespace Icicle\ReactAdaptor\Loop;

use Icicle\Loop\Loop;

/**
 * Adapts Icicle's event loop to React's event loop interface so components requiring a React loop can be used.
 */
class ReactLoop implements \React\EventLoop\LoopInterface
{
    /**
     * @var \Icicle\Loop\Events\SocketEventInterface[]
     */
    private $polls = [];

    /**
     * @var \Icicle\Loop\Events\SocketEventInterface[]
     */
    private $awaits = [];

    /**
     * @inheritdoc
     */
    public function addReadStream($stream, callable $listener)
    {
        $id = (int) $stream;

        $listener = function ($stream) use ($listener, $id) {
            $listener($stream, $this);
            if (isset($this->polls[$id])) {
                $this->polls[$id]->listen();
            }
        };

        if (isset($this->polls[$id])) {
            $this->polls[$id]->setCallback($listener);
        } else {
            $poll = Loop::poll($stream, $listener);
            $poll->listen();
            $this->polls[$id] = $poll;
        }
    }

    /**
     * @inheritdoc
     */
    public function removeReadStream($stream)
    {
        $id = (int) $stream;

        if (isset($this->polls[$id])) {
            $this->polls[$id]->free();
            unset($this->polls[$id]);
        }
    }

    /**
     * @inheritdoc
     */
    public function addWriteStream($stream, callable $listener)
    {
        $id = (int) $stream;

        $listener = function ($stream) use ($listener, $id) {
            $listener($stream, $this);
            if (isset($this->awaits[$id])) {
                $this->awaits[$id]->listen();
            }
        };

        if (isset($this->awaits[$id])) {
            $this->awaits[$id]->setCallback($listener);
        } else {
            $await = Loop::await($stream, $listener);
            $await->listen();
            $this->awaits[$id] = $await;
        }
    }

    /**
     * @inheritdoc
     */
    public function removeWriteStream($stream)
    {
        $id = (int) $stream;

        if (isset($this->awaits[$id])) {
            $this->awaits[$id]->free();
            unset($this->awaits[$id]);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    /**
     * @inheritdoc
     */
    public function addTimer($interval, callable $callback)
    {
        $callback = function () use (&$timer, $callback) {
            $callback($timer);
        };

        return $timer = new ReactTimer($this, Loop::timer($interval, $callback));
    }

    /**
     * @inheritdoc
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $callback = function () use (&$timer, $callback) {
            $callback($timer);
        };

        return $timer = new ReactTimer($this, Loop::periodic($interval, $callback));
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function cancelTimer(\React\EventLoop\Timer\TimerInterface $timer)
    {
        // No-op since the ReactTimer adaptor class will not call this method.
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function isTimerActive(\React\EventLoop\Timer\TimerInterface $timer)
    {
        // No-op since the ReactTimer adaptor class will not call this method.
    }

    /**
     * @inheritdoc
     */
    public function nextTick(callable $listener)
    {
        Loop::schedule($listener, $this);
    }

    /**
     * @inheritdoc
     */
    public function futureTick(callable $listener)
    {
        Loop::immediate($listener, $this);
    }

    /**
     * @inheritdoc
     */
    public function tick()
    {
        Loop::tick();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        Loop::run();
    }

    /**
     * @inheritdoc
     */
    public function stop()
    {
        Loop::stop();
    }
}
