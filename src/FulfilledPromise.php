<?php

namespace React\Promise;

class FulfilledPromise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $value;

    public function __construct($value = null)
    {
        if ($value instanceof PromiseInterface) {
            throw new \InvalidArgumentException('You cannot create React\Promise\FulfilledPromise with a promise. Use React\Promise\resolve($promiseOrValue) instead.');
        }

        $this->value = $value;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if (null === $onFulfilled) {
            return $this;
        }

        try {
            // Explicitly overwrite arguments with null values before invoking
            // resolver function. This ensure that these arguments do not show up
            // in the stack trace in PHP 7+ only.
            $cb = $onFulfilled;
            $onFulfilled = _describeType($onFulfilled);
            $onRejected = _describeType($onRejected);
            $onProgress = _describeType($onProgress);

            return resolve($cb($this->value));
        } catch (\Throwable $exception) {
            return new RejectedPromise($exception);
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }

    public function done(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if (null === $onFulfilled) {
            return;
        }

        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onFulfilled;
        $onFulfilled = _describeType($onFulfilled);
        $onRejected = _describeType($onRejected);
        $onProgress = _describeType($onProgress);

        $result = $cb($this->value);

        if ($result instanceof ExtendedPromiseInterface) {
            $result->done();
        }
    }

    public function otherwise(callable $onRejected)
    {
        return $this;
    }

    public function always(callable $onFulfilledOrRejected)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onFulfilledOrRejected;
        $onFulfilledOrRejected = _describeType($onFulfilledOrRejected);

        return $this->then(function ($value) use ($cb) {
            return resolve($cb())->then(function () use ($value) {
                return $value;
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
