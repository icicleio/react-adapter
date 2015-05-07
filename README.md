# React Adaptor for Icicle

[![@icicleio on Twitter](https://img.shields.io/badge/twitter-%40icicleio-5189c7.svg?style=flat-square)](https://twitter.com/icicleio)
[![Build Status](https://img.shields.io/travis/icicleio/ReactAdaptor/master.svg?style=flat-square)](https://travis-ci.org/icicleio/ReactAdaptor)
[![Coverage Status](https://img.shields.io/coveralls/icicleio/ReactAdaptor.svg?style=flat-square)](https://coveralls.io/r/icicleio/ReactAdaptor)
[![Semantic Version](https://img.shields.io/badge/semver-v0.1.1-yellow.svg?style=flat-square)](http://semver.org)
[![Apache 2 License](https://img.shields.io/packagist/l/icicleio/react-adaptor.svg?style=flat-square)](LICENSE)

[![Join the chat at https://gitter.im/icicleio/Icicle](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/icicleio/Icicle)

This library facilitates interoperability between components built for [React](http://reactphp.org) and [Icicle](http://icicle.io). This library provides an adaptor between the differing event loop and promise implementations of the two libraries.

##### Requirements

- PHP 5.5+

##### Installation

The recommended way to install is with the [Composer](http://getcomposer.org/) package manager. (See the [Composer installation guide](https://getcomposer.org/doc/00-intro.md) for information on installing and using Composer.)

Run the following command to use this library in your project: 

```bash
composer require icicleio/react-adaptor
```

You can also manually edit `composer.json` to add this library as a project requirement.

```js
// composer.json
{
    "require": {
        "icicleio/react-adaptor": "0.1.*"
    }
}
```

## ReactLoop

`Icicle\ReactAdaptor\Loop\ReactLoop` is as a direct replacement for the React event loop. It communicates with the active Icicle event loop to provide the same functionality. The class implements `React\EventLoop\LoopInterface`, so it can be used with any component that requires a React event loop.

```php
use Icicle\ReactAdaptor\Loop\ReactLoop;
use Predis\Async\Client;

// Create the loop adaptor.
$loop = new ReactLoop();

// $loop can be used anywhere an instance of React\EventLoop\LoopInterface is required.
$client = new Client('tcp://127.0.0.1:6379', $loop);
```

## ReactPromise

`Icicle\ReactAdaptor\Promise\ReactPromise` creates a promise implementing `React\Promise\ExtendedPromiseInterface` and `React\Promise\ExtendedPromiseInterface` from an Icicle promise that implements `Icicle\Promise\PromiseInterface`. This allows promises created from Icicle to be used in any component requiring a React promise.

```php
$iciclePromise = new \Icicle\Promise\Promise(function ($resolve, $reject) {
    // Resolver
});

$reactPromise = new \Icicle\ReactAdaptor\Promise\ReactPromise($iciclePromise);
```

## Promise::adapt()

`Icicle\Promise\Promise` includes an `adapt()` method that can transform any object with a `then(callable $onFulfilled, callable $onRejected)` method into a promise implementing `Icicle\Promise\PromiseInterface`. This method can be used to convert a React promise to an Icicle promise.

```php
$reactPromise = new \React\Promise\Promise(function ($resolve, $reject) {
    // Resolver
});

$iciclePromise = \Icicle\Promise\Promise::adapt($reactPromise);
```

See the [Promise API documentation](//github.com/icicleio/Icicle/wiki/Promises) for more information on `Promise::adapt()`.
