<?php

declare(strict_types = 1);

namespace Rx\Operator;

use Rx\Disposable\CompositeDisposable;
use Rx\Disposable\SingleAssignmentDisposable;
use Rx\DisposableInterface;
use Rx\ObservableInterface;
use Rx\Observer\CallbackObserver;
use Rx\ObserverInterface;

final class MergeAllOperator implements OperatorInterface
{
    public function __invoke(ObservableInterface $observable, ObserverInterface $observer): DisposableInterface
    {
        $group              = new CompositeDisposable();
        $isStopped          = false;
        $sourceSubscription = new SingleAssignmentDisposable();

        $group->add($sourceSubscription);

        $callbackObserver = new CallbackObserver(
            function (ObservableInterface $innerSource) use (&$group, &$isStopped, $observer): void {
                $innerSubscription = new SingleAssignmentDisposable();
                $group->add($innerSubscription);

                $innerSubscription->setDisposable(
                    $innerSource->subscribe(new CallbackObserver(
                        function ($nextValue) use ($observer): void {
                            $observer->onNext($nextValue);
                        },
                        function ($error) use ($observer): void {
                            $observer->onError($error);
                        },
                        function () use (&$group, &$innerSubscription, &$isStopped, $observer): void {
                            $group->remove($innerSubscription);

                            if ($isStopped && $group->count() === 1) {
                                $observer->onCompleted();
                            }
                        }
                    ))
                );
            },
            fn ($err) => $observer->onError($err),
            function () use (&$group, &$isStopped, $observer): void {
                $isStopped = true;
                if ($group->count() === 1) {
                    $observer->onCompleted();
                }
            }
        );

        $subscription = $observable->subscribe($callbackObserver);

        $sourceSubscription->setDisposable($subscription);

        return $group;
    }
}
