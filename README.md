# React Adapter for Icicle

[![@icicleio on Twitter](https://img.shields.io/badge/twitter-%40icicleio-5189c7.svg?style=flat-square)](https://twitter.com/icicleio)
[![Build Status](https://img.shields.io/travis/icicleio/react-adapter/master.svg?style=flat-square)](https://travis-ci.org/icicleio/react-adapter)
[![Coverage Status](https://img.shields.io/coveralls/icicleio/react-adapter.svg?style=flat-square)](https://coveralls.io/r/icicleio/react-adapter)
[![Semantic Version](https://img.shields.io/github/release/icicleio/react-adapter.svg?style=flat-square)](http://semver.org)
[![MIT License](https://img.shields.io/packagist/l/icicleio/react-adapter.svg?style=flat-square)](LICENSE)

This library facilitates interoperability between components built for [React](http://reactphp.org) and [Icicle](http://icicle.io). This library provides an adapter between the differing event loop and promise implementations of the two libraries.

##### Requirements

- PHP 5.5+

##### Installation

The recommended way to install is with the [Composer](http://getcomposer.org/) package manager. (See the [Composer installation guide](https://getcomposer.org/doc/00-intro.md) for information on installing and using Composer.)

Run the following command to use this library in your project: 

```bash
composer require icicleio/react-adapter
```

You can also manually edit `composer.json` to add this library as a project requirement.

```js
// composer.json
{
    "require": {
        "icicleio/react-adapter": "^0.4"
    }
}
```

## ReactLoop

`Icicle\ReactAdapter\Loop\ReactLoop` is as a direct replacement for the React event loop. It communicates with the active Icicle event loop to provide the same functionality. The class implements `React\EventLoop\LoopInterface`, so it can be used with any component that requires a React event loop.

```php
use Icicle\ReactAdapter\Loop\ReactLoop;
use Predis\Async\Client;

// Create the loop adapter.
$loop = new ReactLoop();

// $loop can be used anywhere an instance of React\EventLoop\LoopInterface is required.
$client = new Client('tcp://127.0.0.1:6379', $loop);
```

## ReactPromise

`Icicle\ReactAdapter\Promise\ReactPromise` creates a promise implementing `React\Promise\ExtendedPromiseInterface` and `React\Promise\ExtendedPromiseInterface` from an Icicle awaitable that implements `Icicle\Awaitable\Awaitable`. This allows awaitables created from Icicle to be used in any component requiring a React promise.

```php
$iciclePromise = new \Icicle\Awaitable\Promise(function ($resolve, $reject) {
    // Resolver
});

$reactPromise = new \Icicle\ReactAdapter\Promise\ReactPromise($iciclePromise);
```

## Awaitable\adapt()

The `Icicle\Awaitable` namespace defines a function `adapt()` that can transform any object with a `then(callable $onFulfilled, callable $onRejected)` method into an awaitable implementing `Icicle\Awaitable\Awaitable`. This function can be used to convert a React promise to an Icicle awaitable.

```php
$reactPromise = new \React\Promise\Promise(function ($resolve, $reject) {
    // Resolver
});

$awaitable = \Icicle\Awaitable\adapt($reactPromise);
```

See the [Awaitable API documentation](//github.com/icicleio/icicle/wiki/Awaitables) for more information on `Icicle\Awaitable\adapt()`.
