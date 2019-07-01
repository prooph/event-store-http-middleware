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

namespace ProophTest\EventStore\Http\Middleware\Container\Action;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Http\Middleware\Action\StopProjection;
use Prooph\EventStore\Http\Middleware\Container\Action\StopProjectionFactory;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class StopProjectionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_new_delete_stream_action(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ProjectionManager::class)->willReturn($projectionManager->reveal())->shouldBeCalled();
        $container->get(ResponseFactoryInterface::class)->willReturn($responseFactory->reveal())->shouldBeCalled();

        $factory = new StopProjectionFactory();
        $stream = $factory->__invoke($container->reveal());

        $this->assertInstanceOf(StopProjection::class, $stream);
    }
}
