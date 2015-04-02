<?php
namespace Icicle\Tests\ReactAdaptor\Promise;

use Exception;
use Icicle\Loop\Loop;
use Icicle\Promise\Promise;
use Icicle\ReactAdaptor\Promise\ReactPromise;
use Icicle\Tests\TestCase;

class ReactPromiseTest extends TestCase
{
    public function tearDown()
    {
        Loop::clear();
    }

    public function testConstructWithFulfilled()
    {
        $value = 1;

        $promise = Promise::resolve($value);

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($value));

        $promise->done($callback, $this->createCallback(0));

        Loop::run();
    }

    public function testConstructWithRejected()
    {
        $exception = new Exception();

        $promise = Promise::reject($exception);

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise->done($this->createCallback(0), $callback);

        Loop::run();
    }

    public function testConstructWithPendingThatFulfills()
    {
        $value = 1;

        $promise = new Promise(function ($resolve, $reject) use ($value) {
            Loop::schedule($resolve, $value);
        });

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($value));

        $promise->done($callback, $this->createCallback(0));

        Loop::run();
    }

    public function testConstructWithPendingThatRejects()
    {
        $exception = new Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            Loop::schedule($reject, $exception);
        });

        $promise = new ReactPromise($promise);

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise->done($this->createCallback(0), $callback);

        Loop::run();
    }

    public function testCancel()
    {
        $promise = new Promise(function ($resolve, $reject) {});

        $promise->done($this->createCallback(0), $this->createCallback(1));

        $promise = new ReactPromise($promise);

        $promise->cancel();

        Loop::run();
    }
}
