<?php
namespace Icicle\ReactAdaptor\Promise;

use Icicle\Promise\PromiseInterface;

class ReactPromise extends \React\Promise\LazyPromise
{
    /**
     * @param   \Icicle\Promise\PromiseInterface $promise
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
