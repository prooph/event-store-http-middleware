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

namespace ProophTest\EventStore\Http\Middleware\Action;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Http\Middleware\Action\UpdateStreamMetadata;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateStreamMetadataTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_415_when_invalid_accept_header_sent(): void
    {
        $eventStore = $this->prophesize(EventStore::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo\bar')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(415)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new UpdateStreamMetadata($eventStore->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_404_when_stream_not_found(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->updateStreamMetadata(new StreamName('unknown'), ['foo' => 'bar'])->willThrow(new StreamNotFound());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('unknown')->shouldBeCalled();
        $request->getParsedBody()->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new UpdateStreamMetadata($eventStore->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_will_update_stream_metadata(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->updateStreamMetadata(new StreamName('foo\bar'), ['foo' => 'bar'])->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo\bar')->shouldBeCalled();
        $request->getParsedBody()->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new UpdateStreamMetadata($eventStore->reveal(), $responseFactory->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
