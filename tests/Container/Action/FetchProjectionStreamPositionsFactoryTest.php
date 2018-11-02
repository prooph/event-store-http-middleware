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

namespace ProophTest\EventStore\Http\Middleware\Container\Action;

use Interop\Http\Factory\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Http\Middleware\Action\FetchProjectionStreamPositions;
use Prooph\EventStore\Http\Middleware\Container\Action\FetchProjectionStreamPositionsFactory;
use Prooph\EventStore\Http\Middleware\JsonTransformer;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Container\ContainerInterface;

class FetchProjectionStreamPositionsFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_action_handler(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $transformer = $this->prophesize(Transformer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ProjectionManager::class)->willReturn($projectionManager->reveal())->shouldBeCalled();
        $container->get(ResponseFactoryInterface::class)->willReturn($responseFactory->reveal())->shouldBeCalled();
        $container->get(JsonTransformer::class)->willReturn($transformer->reveal())->shouldBeCalled();

        $factory = new FetchProjectionStreamPositionsFactory();

        $actionHandler = $factory($container->reveal());

        $this->assertInstanceOf(FetchProjectionStreamPositions::class, $actionHandler);
    }
}
