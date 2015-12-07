<?php
namespace Icicle\ReactAdapter\Loop;

use Icicle\Loop\Watcher\Timer;

class ReactTimer implements \React\EventLoop\Timer\TimerInterface
{
    /**
     * @var \Icicle\ReactAdapter\Loop\ReactLoop
     */
    private $loop;

    /**
     * @var \Icicle\Loop\Watcher\Timer
     */
    private $timer;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param \Icicle\ReactAdapter\Loop\ReactLoop $loop
     * @param \Icicle\Loop\Watcher\Timer $timer
     */
    public function __construct(ReactLoop $loop, Timer $timer)
    {
        $this->loop = $loop;
        $this->timer = $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallback()
    {
        return $this->timer;
    }

    /**
     * {@inheritdoc}
     */
    public function getInterval()
    {
        return $this->timer->getInterval();
    }

    /**
     * {@inheritdoc}
     */
    public function isPeriodic()
    {
        return $this->timer->isPeriodic();
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->timer->isPending();
    }

    /**
     * {@inheritdoc}
     */
    public function cancel()
    {
        $this->timer->stop();
    }
}
