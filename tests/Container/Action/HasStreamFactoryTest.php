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
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\HasStream;
use Prooph\EventStore\Http\Middleware\Container\Action\HasStreamFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class HasStreamFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_new_fetch_stream_metadata_action(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(EventStore::class)->willReturn($eventStore->reveal())->shouldBeCalled();
        $container->get(ResponseFactoryInterface::class)->willReturn($responseFactory->reveal())->shouldBeCalled();

        $factory = new HasStreamFactory();
        $stream = $factory->__invoke($container->reveal());

        $this->assertInstanceOf(HasStream::class, $stream);
    }
}
