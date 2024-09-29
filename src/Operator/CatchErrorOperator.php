<?php

declare(strict_types = 1);

namespace Rx\Operator;

use Rx\Disposable\CallbackDisposable;
use Rx\Disposable\CompositeDisposable;
use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObservableInterface;
use Rx\Observer\CallbackObserver;
use Rx\ObserverInterface;

final class CatchErrorOperator implements OperatorInterface
{

    /** @var callable */
    private $errorSelector;

    public function __construct(callable $errorSelector)
    {
        $this->errorSelector = $errorSelector;
    }

    public function __invoke(ObservableInterface $observable, ObserverInterface $observer): DisposableInterface
    {
        $isDisposed = false;
        $disposable = new CompositeDisposable();

        $onError = function (\Throwable $e) use (&$isDisposed, $observer, $observable, $disposable): void {

            if ($isDisposed) {
                return;
            }

            try {
                /** @var Observable $result */
                $result = ($this->errorSelector)($e, $observable);

                $subscription = $result->subscribe($observer);

                $disposable->add($subscription);

            } catch (\Throwable $e) {
                $observer->onError($e);
            }
        };

        $callbackObserver = new CallbackObserver(
            fn ($x) => $observer->onNext($x),
            $onError,
            fn () => $observer->onCompleted(),
        );

        $subscription = $observable->subscribe($callbackObserver);

        $disposable->add($subscription);

        $disposable->add(new CallbackDisposable(function () use (&$isDisposed): void {
            $isDisposed = true;
        }));

        return $disposable;
    }
}
