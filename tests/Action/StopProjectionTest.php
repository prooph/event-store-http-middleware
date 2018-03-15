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

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Http\Middleware\Action\StopProjection;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StopProjectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_delete_projection(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->stopProjection('runner')->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new StopProjection($projectionManager->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_404_when_unknown_projection_asked(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->stopProjection('runner')->willThrow(new ProjectionNotFound())->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('runner')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new StopProjection($projectionManager->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
