<?php

/**
 * This file is part of prooph/event-store-http-middleware.
 * (c) 2018-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2018-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Http\Middleware\Container\Action;

use Prooph\EventStore\Http\Middleware\Action\FetchProjectionStatus;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class FetchProjectionStatusFactory
{
    public function __invoke(ContainerInterface $container): FetchProjectionStatus
    {
        $actionHandler = new FetchProjectionStatus($container->get(ProjectionManager::class), $container->get(ResponseFactoryInterface::class));

        return $actionHandler;
    }
}
