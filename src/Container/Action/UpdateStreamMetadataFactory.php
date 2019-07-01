<?php

/**
 * This file is part of prooph/event-store-http-middleware.
 * (c) 2018-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2018-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Http\Middleware\Container\Action;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\UpdateStreamMetadata;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class UpdateStreamMetadataFactory
{
    public function __invoke(ContainerInterface $container): UpdateStreamMetadata
    {
        return new UpdateStreamMetadata($container->get(EventStore::class), $container->get(ResponseFactoryInterface::class));
    }
}
