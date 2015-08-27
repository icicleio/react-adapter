<?php
namespace Icicle\ReactAdapter\Promise;

use Icicle\Promise\PromiseInterface;
use React\Promise\LazyPromise;

class ReactPromise extends LazyPromise
{
    /**
     * @param \Icicle\Promise\PromiseInterface $promise
     */
    public function __construct(PromiseInterface $promise)
    {
        parent::__construct(function () use ($promise) {
            return new \React\Promise\Promise(
                function ($resolve, $reject) use ($promise) {
                    $promise->done($resolve, $reject);
                },
                function () use ($promise) {
                    $promise->cancel();
                }
            );
        });
    }
}
