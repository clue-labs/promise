<?php

namespace React\Promise;

class RejectedPromise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $reason;

    public function __construct($reason = null)
    {
        if ($reason instanceof PromiseInterface) {
            throw new \InvalidArgumentException('You cannot create React\Promise\RejectedPromise with a promise. Use React\Promise\reject($promiseOrValue) instead.');
        }

        $this->reason = $reason;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if (null === $onRejected) {
            return $this;
        }

        try {
            // Explicitly overwrite arguments with null values before invoking
            // resolver function. This ensure that these arguments do not show up
            // in the stack trace in PHP 7+ only.
            $cb = $onRejected;
            $onFulfilled = _describeType($onFulfilled);
            $onRejected = _describeType($onRejected);
            $onProgress = _describeType($onProgress);

            return resolve($cb($this->reason));
        } catch (\Throwable $exception) {
            return new RejectedPromise($exception);
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }

    public function done(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onRejected;
        $onFulfilled = _describeType($onFulfilled);
        $onRejected = _describeType($onRejected);
        $onProgress = _describeType($onProgress);

        if (null === $cb) {
            throw UnhandledRejectionException::resolve($this->reason);
        }

        $result = $cb($this->reason);

        if ($result instanceof self) {
            throw UnhandledRejectionException::resolve($result->reason);
        }

        if ($result instanceof ExtendedPromiseInterface) {
            $result->done();
        }
    }

    public function otherwise(callable $onRejected)
    {
        if (!_checkTypehint($onRejected, $this->reason)) {
            return $this;
        }

        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onRejected;
        $onRejected = null;

        return $this->then(null, $cb);
    }

    public function always(callable $onFulfilledOrRejected)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onFulfilledOrRejected;
        $onFulfilledOrRejected = null;

        return $this->then(null, function ($reason) use ($cb) {
            return resolve($cb())->then(function () use ($reason) {
                return new RejectedPromise($reason);
            });
        });
    }

    public function progress(callable $onProgress)
    {
        return $this;
    }

    public function cancel()
    {
    }
}
