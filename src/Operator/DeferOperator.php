<?php

declare(strict_types = 1);

namespace Rx\Operator;

use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObservableInterface;
use Rx\ObserverInterface;

final class DeferOperator implements OperatorInterface
{

    public function __construct(private $factory)
    {
    }

    public function __invoke(ObservableInterface $observable, ObserverInterface $observer): DisposableInterface
    {
        $factory = $this->factory;

        try {
            $result = $factory();
        } catch (\Throwable $e) {
            return Observable::error($e)->subscribe($observer);
        }

        return $result->subscribe($observer);
    }
}
