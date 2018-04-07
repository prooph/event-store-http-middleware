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

namespace ProophTest\EventStore\Http\Middleware\Action;

use Interop\Http\Factory\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Http\Middleware\Action\ResetProjection;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResetProjectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_delete_projection(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->resetProjection('runner')->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new ResetProjection($projectionManager->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_404_when_unknown_projection_asked(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->resetProjection('runner')->willThrow(new ProjectionNotFound())->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new ResetProjection($projectionManager->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
