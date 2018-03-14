<?php
/**
 * This file is part of the prooph/event-store-http-middleware.
 * (c) 2016-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Http\Middleware\Container\Action;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\DeleteStream;
use Prooph\EventStore\Http\Middleware\Container\Action\DeleteStreamFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class DeleteStreamFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_new_delete_stream_action(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $responsePrototype = $this->prophesize(ResponseInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(EventStore::class)->willReturn($eventStore->reveal())->shouldBeCalled();
        $container->get(ResponseInterface::class)->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $factory = new DeleteStreamFactory();
        $action = $factory->__invoke($container->reveal());

        $this->assertInstanceOf(DeleteStream::class, $action);
    }
}
