<?php

/**
 * This file is part of prooph/event-store-http-middleware.
 * (c) 2018-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2018-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Http\Middleware\Container\Action;

use Interop\Http\Factory\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\PostStream;
use Prooph\EventStore\Http\Middleware\Container\Action\PostStreamFactory;
use Prooph\EventStore\Http\Middleware\GenericEventFactory;
use Psr\Container\ContainerInterface;

class PostStreamFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_new_post_action(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(EventStore::class)->willReturn($eventStore->reveal())->shouldBeCalled();
        $container->get(GenericEventFactory::class)->willReturn($messageFactory->reveal())->shouldBeCalled();
        $container->get(ResponseFactoryInterface::class)->willReturn($responseFactory->reveal())->shouldBeCalled();

        $factory = new PostStreamFactory();
        $stream = $factory->__invoke($container->reveal());

        $this->assertInstanceOf(PostStream::class, $stream);
    }
}
