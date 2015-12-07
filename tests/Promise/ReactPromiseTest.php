<?php
namespace Icicle\Tests\ReactAdapter\Promise;

use Exception;
use Icicle\Awaitable;
use Icicle\Awaitable\Promise;
use Icicle\Loop;
use Icicle\Loop\SelectLoop;
use Icicle\ReactAdapter\Promise\ReactPromise;
use Icicle\Tests\ReactAdapter\TestCase;

class ReactPromiseTest extends TestCase
{
    public function setUp()
    {
        Loop\loop(new SelectLoop());
    }

    public function testConstructWithFulfilled()
    {
        $value = 1;

        $promise = Awaitable\resolve($value);

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($value));

        $promise->done($callback, $this->createCallback(0));

        Loop\run();
    }

    public function testConstructWithRejected()
    {
        $exception = new Exception();

        $promise = Awaitable\reject($exception);

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise->done($this->createCallback(0), $callback);

        Loop\run();
    }

    public function testConstructWithPendingThatFulfills()
    {
        $value = 1;

        $promise = new Promise(function ($resolve, $reject) use ($value) {
            Loop\queue($resolve, $value);
        });

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($value));

        $promise->done($callback, $this->createCallback(0));

        Loop\run();
    }

    public function testConstructWithPendingThatRejects()
    {
        $exception = new Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            Loop\queue($reject, $exception);
        });

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise->done($this->createCallback(0), $callback);

        Loop\run();
    }

    public function testCancel()
    {
        $promise = new Promise(function ($resolve, $reject) {});

        $promise->done($this->createCallback(0), $this->createCallback(1));

        $promise = new ReactPromise($promise);

        $promise->cancel();

        Loop\run();
    }
}
