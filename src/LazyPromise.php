<?php

namespace React\Promise;

class LazyPromise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $factory;
    private $promise;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cbFulfilled = $onFulfilled;
        $cbRejected = $onRejected;
        $cbProgress = $onProgress;
        $onFulfilled = _describeType($onFulfilled);
        $onRejected = _describeType($onRejected);
        $onProgress = _describeType($onProgress);

        return $this->promise()->then($cbFulfilled, $cbRejected, $cbProgress);
    }

    public function done(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cbFulfilled = $onFulfilled;
        $cbRejected = $onRejected;
        $cbProgress = $onProgress;
        $onFulfilled = _describeType($onFulfilled);
        $onRejected = _describeType($onRejected);
        $onProgress = _describeType($onProgress);

        return $this->promise()->done($cbFulfilled, $cbRejected, $cbProgress);
    }

    public function otherwise(callable $onRejected)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onRejected;
        $onRejected = _describeType($onRejected);

        return $this->promise()->otherwise($cb);
    }

    public function always(callable $onFulfilledOrRejected)
    {
        // Explicitly overwrite arguments with null values before invoking
        // resolver function. This ensure that these arguments do not show up
        // in the stack trace in PHP 7+ only.
        $cb = $onFulfilledOrRejected;
        $onFulfilledOrRejected = _describeType($onFulfilledOrRejected);

        return $this->promise()->always($cb);
    }

    public function progress(callable $onProgress)
    {
        return $this->promise()->progress($onProgress);
    }

    public function cancel()
    {
        return $this->promise()->cancel();
    }

    /**
     * @internal
     * @see Promise::settle()
     */
    public function promise()
    {
        if (null === $this->promise) {
            try {
                $this->promise = resolve(call_user_func($this->factory));
            } catch (\Throwable $exception) {
                $this->promise = new RejectedPromise($exception);
            } catch (\Exception $exception) {
                $this->promise = new RejectedPromise($exception);
            }
        }

        return $this->promise;
    }
}
