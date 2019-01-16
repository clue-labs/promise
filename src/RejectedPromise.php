<?php

namespace React\Promise;

class RejectedPromise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $reason;
    private $handled = false;

    public function __construct($reason = null)
    {
        if ($reason instanceof PromiseInterface) {
            throw new \InvalidArgumentException('You cannot create React\Promise\RejectedPromise with a promise. Use React\Promise\reject($promiseOrValue) instead.');
        }

        $this->reason = $reason;
    }

    public function __destruct()
    {
        if ($this->handled) {
            return;
        }

        $message = 'Unhandled promise rejection with ';

        if ($this->reason instanceof \Throwable || $this->reason instanceof \Exception) {
            $message .= get_class($this->reason) . ': ' . $this->reason->getMessage();
            $message .= ' raised in ' . $this->reason->getFile() . ' on line ' . $this->reason->getLine();
            $message .= PHP_EOL . $this->reason->getTraceAsString();
        } else {
            if ($this->reason === null) {
                $message .= 'null';
            } else {
                $message .= (is_object($this->reason) ? get_class($this->reason) : gettype($this->reason));
            }

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            if (isset($trace[0]['file'], $trace[0]['line'])) {
                $message .= ' detected in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'];
            }

            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $message .= PHP_EOL . ob_get_clean();
        }

        $message .= PHP_EOL;
        echo $message;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if (null === $onRejected) {
            return $this;
        }

        $this->handled = true;

        try {
            return resolve($onRejected($this->reason));
        } catch (\Throwable $exception) {
            return new RejectedPromise($exception);
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }

    public function done(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        $this->handled = true;

        if (null === $onRejected) {
            throw UnhandledRejectionException::resolve($this->reason);
        }

        $result = $onRejected($this->reason);

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

        return $this->then(null, $onRejected);
    }

    public function always(callable $onFulfilledOrRejected)
    {
        return $this->then(null, function ($reason) use ($onFulfilledOrRejected) {
            return resolve($onFulfilledOrRejected())->then(function () use ($reason) {
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
