<?php

namespace React\Promise;

use React\Promise\PromiseAdapter\CallbackPromiseAdapter;

class RejectedPromiseTest extends TestCase
{
    use PromiseTest\PromiseSettledTestTrait,
        PromiseTest\PromiseRejectedTestTrait;

    public function getPromiseTestAdapter(callable $canceller = null)
    {
        $promise = null;

        return new CallbackPromiseAdapter([
            'promise' => function () use (&$promise) {
                if (!$promise) {
                    throw new \LogicException('RejectedPromise must be rejected before obtaining the promise');
                }

                return $promise;
            },
            'resolve' => function () {
                throw new \LogicException('You cannot call resolve() for React\Promise\RejectedPromise');
            },
            'reject' => function ($reason = null) use (&$promise) {
                if (!$promise) {
                    $promise = new RejectedPromise($reason);
                }
            },
            'notify' => function () {
                // no-op
            },
            'settle' => function ($reason = null) use (&$promise) {
                if (!$promise) {
                    $promise = new RejectedPromise($reason);
                }
            },
        ]);
    }

    /** @test */
    public function shouldThrowExceptionIfConstructedWithAPromise()
    {
        $this->setExpectedException('\InvalidArgumentException');

        new RejectedPromise(new FulfilledPromise());
    }

    /** @test */
    public function shouldReportUnhandledRejectionWhenUnset()
    {
        $this->expectOutputRegex('/Unhandled promise rejection with null detected in/');

        $reject = new RejectedPromise();
        unset($reject);
    }

    /** @test */
    public function shouldReportUnhandledRejectionWithStringWhenUnset()
    {
        $this->expectOutputRegex('/Unhandled promise rejection with string detected in/');

        $reject = new RejectedPromise('hello');
        unset($reject);
    }

    /** @test */
    public function shouldReportUnhandledRejectionWhenOnlyHandlingResolution()
    {
        $this->expectOutputRegex('/Unhandled promise rejection with null detected in/');

        $reject = new RejectedPromise();
        $reject->then($this->expectCallableNever());
        unset($reject);
    }

    /** @test */
    public function shouldNotReportUnhandledRejectionWhenHandlingRejection()
    {
        $this->expectOutputString('');

        $reject = new RejectedPromise();
        $reject->then(null, $this->expectCallableOnce());
        unset($reject);
    }

    /** @test */
    public function shouldReportUnhandledRejectionWhenThrowingFromRejectionHandler()
    {
        $this->expectOutputRegex('/Unhandled promise rejection with RuntimeException: Demo raised in/');

        $reject = new RejectedPromise();
        $reject->then(null, function () {
            throw new \RuntimeException('Demo');
        });
        unset($reject);
    }

    /**
     * @test
     * @requires PHP 7
     */
    public function shouldReportUnhandledRejectionForTypeErrorOnRejectionHandlerSignature()
    {
        $this->expectOutputRegex('/Unhandled promise rejection with TypeError: Argument 1 passed to .* must be an instance of stdClass, null given/');

        $reject = new RejectedPromise();
        $reject->then(null, function (\StdClass $_) {
            // not called due to type mismatch
        });
        unset($reject);
    }

    /** @test */
    public function shouldNotReportUnhandledRejectionWhenDoneHandled()
    {
        $this->expectOutputString('');

        $reject = new RejectedPromise();
        try {
            $reject->done();
        } catch (\React\Promise\UnhandledRejectionException $ignore) {
            unset($ignore);
        }
        unset($reject);
    }

    /** @test */
    public function shouldNotLeaveGarbageCyclesWhenRemovingLastReferenceToRejectedPromiseWithAlwaysFollowers()
    {
        gc_collect_cycles();
        $promise = new RejectedPromise(1);
        $ret = $promise->always(function () {
            throw new \RuntimeException();
        });
        $ret->then(null, function () { });
        unset($ret,$promise);

        $this->assertSame(0, gc_collect_cycles());
    }

    /** @test */
    public function shouldNotLeaveGarbageCyclesWhenRemovingLastReferenceToRejectedPromiseWithThenFollowers()
    {
        gc_collect_cycles();
        $promise = new RejectedPromise(1);
        $promise = $promise->then(null, function () {
            throw new \RuntimeException();
        });
        $promise->then(null, function () { });
        unset($promise);

        $this->assertSame(0, gc_collect_cycles());
    }
}
