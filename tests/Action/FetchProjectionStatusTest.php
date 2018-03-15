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
use Prooph\EventStore\Http\Middleware\Action\FetchProjectionStatus;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FetchProjectionStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_status(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->fetchProjectionStatus('foo')->willReturn(ProjectionStatus::RUNNING())->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('foo')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(200, ProjectionStatus::RUNNING()->getName())->willReturn($responsePrototype)->shouldBeCalled();

        $action = new FetchProjectionStatus($projectionManager->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_404_on_unknown_projection(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->fetchProjectionStatus('unknown')->willThrow(ProjectionNotFound::withName('unknown'))->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('name')->willReturn('unknown')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new FetchProjectionStatus($projectionManager->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
