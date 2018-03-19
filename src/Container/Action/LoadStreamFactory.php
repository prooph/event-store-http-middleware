<?php
/**
 * This file is part of the prooph/event-store-http-middleware.
 * (c) 2018-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2018-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Http\Middleware\Container\Action;

use Prooph\Common\Messaging\MessageConverter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\LoadStream;
use Prooph\EventStore\Http\Middleware\ResponsePrototype;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Http\Middleware\UrlHelper;
use Psr\Container\ContainerInterface;

final class LoadStreamFactory
{
    public function __invoke(ContainerInterface $container): LoadStream
    {
        $actionHandler = new LoadStream(
            $container->get(EventStore::class),
            $container->get(MessageConverter::class),
            $container->get(UrlHelper::class),
            $container->get(ResponsePrototype::class)
        );

        $actionHandler->addTransformer(
            $container->get(Transformer::class),

        );

        return $actionHandler;
    }
}
