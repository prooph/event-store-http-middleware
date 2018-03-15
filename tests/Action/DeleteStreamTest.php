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
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Http\Middleware\Action\DeleteStream;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeleteStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_404_when_stream_not_found(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->delete(new StreamName('unknown'))->willThrow(new StreamNotFound());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('streamname')->willReturn('unknown')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new DeleteStream($eventStore->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_will_delete_stream(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->delete(new StreamName('foo\bar'))->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('streamname')->willReturn('foo\bar')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new DeleteStream($eventStore->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
