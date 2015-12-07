<?php
namespace Icicle\ReactAdapter;

use Icicle\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

/**
 * Adapts Icicle's event loop to React's event loop interface so components requiring a React loop can be used.
 */
class ReactLoop implements LoopInterface
{
    /**
     * @var \Icicle\Loop\Loop
     */
    private $loop;

    /**
     * @var \Icicle\Loop\Watcher\Io[]
     */
    private $polls = [];

    /**
     * @var \Icicle\Loop\Watcher\Io[]
     */
    private $awaits = [];

    /**
     * @param \Icicle\Loop\Loop|null $loop
     */
    public function __construct(Loop\Loop $loop = null)
    {
        $this->loop = $loop ?: Loop\loop();
    }

    /**
     * {@inheritdoc}
     */
    public function addReadStream($stream, callable $listener)
    {
        $id = (int) $stream;

        $listener = function ($stream) use ($listener, $id) {
            $listener($stream, $this);
        };

        if (isset($this->polls[$id])) {
            $this->polls[$id]->free();
        }

        $poll = $this->loop->poll($stream, $listener, true);
        $poll->listen();
        $this->polls[$id] = $poll;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function addWriteStream($stream, callable $listener)
    {
        $id = (int) $stream;

        $listener = function ($stream) use ($listener, $id) {
            $listener($stream, $this);
        };

        if (isset($this->awaits[$id])) {
            $this->awaits[$id]->free();
        }

        $await = $this->loop->await($stream, $listener, true);
        $await->listen();
        $this->awaits[$id] = $await;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer($interval, callable $callback)
    {
        $callback = function () use (&$timer, $callback) {
            $callback($timer);
        };

        return $timer = new ReactTimer($this, $this->loop->timer($interval, false, $callback));
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $callback = function () use (&$timer, $callback) {
            $callback($timer);
        };

        return $timer = new ReactTimer($this, $this->loop->timer($interval, true, $callback));
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function cancelTimer(TimerInterface $timer)
    {
        // No-op since the ReactTimer adapter class will not call this method.
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function isTimerActive(TimerInterface $timer)
    {
        // No-op since the ReactTimer adapter class will not call this method.
    }

    /**
     * {@inheritdoc}
     */
    public function nextTick(callable $listener)
    {
        $this->loop->queue($listener, [$this]);
    }

    /**
     * {@inheritdoc}
     */
    public function futureTick(callable $listener)
    {
        $this->loop->immediate($listener, [$this]);
    }

    /**
     * {@inheritdoc}
     */
    public function tick()
    {
        $this->loop->tick();
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->loop->run();
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->loop->stop();
    }
}
