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

namespace ProophTest\EventStore\Http\Middleware\Action;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Http\Middleware\Action\DeleteProjection;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeleteProjectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_delete_projection_incl_emitted_events(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->deleteProjection('runner', true)->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();
        $request->getAttribute('deleteEmittedEvents')->willReturn('true')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new DeleteProjection($projectionManager->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_will_delete_projection_without_emitted_events(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->deleteProjection('runner', false)->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();
        $request->getAttribute('deleteEmittedEvents')->willReturn('false')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new DeleteProjection($projectionManager->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_404_when_unknown_projection_asked(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();
        $request->getAttribute('deleteEmittedEvents')->willReturn('true')->shouldBeCalled();

        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->deleteProjection('runner', true)->willThrow(new ProjectionNotFound())->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new DeleteProjection($projectionManager->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
