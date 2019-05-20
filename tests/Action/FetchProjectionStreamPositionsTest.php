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
use Prooph\EventStore\Http\Middleware\Action\FetchProjectionStreamPositions;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FetchProjectionStreamPositionsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_415_when_invalid_accept_header_sent(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(415)->willReturn($responsePrototype)->shouldBeCalled();

        $transformer = $this->prophesize(Transformer::class);

        $action = new FetchProjectionStreamPositions($projectionManager->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_stream_positions(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->fetchProjectionStreamPositions('foo')->willReturn(['foo' => 100, 'bar' => 200])->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/atom+json')->shouldBeCalled();
        $request->getAttribute('name')->willReturn('foo')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), ['foo' => 100, 'bar' => 200])->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new FetchProjectionStreamPositions($projectionManager->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_404_on_unknown_projection(): void
    {
        $projectionManager = $this->prophesize(ProjectionManager::class);
        $projectionManager->fetchProjectionStreamPositions('unknown')->willThrow(ProjectionNotFound::withName('unknown'))->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/atom+json')->shouldBeCalled();
        $request->getAttribute('name')->willReturn('unknown')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(404)->willReturn($responsePrototype)->shouldBeCalled();

        $transformer = $this->prophesize(Transformer::class);

        $action = new FetchProjectionStreamPositions($projectionManager->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
