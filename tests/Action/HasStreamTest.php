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
use Prooph\EventStore\Http\Middleware\Action\HasStream;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HasStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_404_when_stream_not_found(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('unknown'))->willReturn(false)->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('streamname')->willReturn('unknown')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(404)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new HasStream($eventStore->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_200_when_stream_found(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('known'))->willReturn(true)->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('streamname')->willReturn('known')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(200)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new HasStream($eventStore->reveal(), $responsePrototype->reveal());

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
