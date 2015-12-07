<?php
namespace Icicle\ReactAdapter\Promise;

use Icicle\Awaitable\Awaitable;
use React\Promise\LazyPromise;
use React\Promise\Promise;

class ReactPromise extends LazyPromise
{
    /**
     * @param \Icicle\Awaitable\Awaitable $awaitable
     */
    public function __construct(Awaitable $awaitable)
    {
        parent::__construct(function () use ($awaitable) {
            return new Promise(
                function ($resolve, $reject) use ($awaitable) {
                    $awaitable->done($resolve, $reject);
                },
                function () use ($awaitable) {
                    $awaitable->cancel();
                }
            );
        });
    }
}
