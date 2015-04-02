<?php
namespace Icicle\ReactAdaptor\Loop;

use Icicle\Loop\Events\TimerInterface;

class ReactTimer implements \React\EventLoop\Timer\TimerInterface
{
    /**
     * @var \Icicle\ReactAdaptor\Loop\ReactLoop
     */
    private $loop;

    /**
     * @var \Icicle\Loop\Events\TimerInterface
     */
    private $timer;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param   \Icicle\ReactAdaptor\Loop\ReactLoop $loop
     * @param   \Icicle\Loop\Events\TimerInterface $timer
     */
    public function __construct(ReactLoop $loop, TimerInterface $timer)
    {
        $this->loop = $loop;
        $this->timer = $timer;
    }

    public function getLoop()
    {
        return $this->loop;
    }

    public function getCallback()
    {
        return $this->timer->getCallback();
    }

    public function getInterval()
    {
        return $this->timer->getInterval();
    }

    public function isPeriodic()
    {
        return $this->timer->isPeriodic();
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function isActive()
    {
        return $this->timer->isPending();
    }

    public function cancel()
    {
        $this->timer->cancel();
    }
}
