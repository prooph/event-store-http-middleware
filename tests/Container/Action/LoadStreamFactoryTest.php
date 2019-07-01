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
use Prooph\Common\Messaging\MessageConverter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\LoadStream;
use Prooph\EventStore\Http\Middleware\Container\Action\LoadStreamFactory;
use Prooph\EventStore\Http\Middleware\JsonTransformer;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Http\Middleware\UrlHelper;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class LoadStreamFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_new_load_action(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageConverter = $this->prophesize(MessageConverter::class);
        $urlHelper = $this->prophesize(UrlHelper::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $transformer = $this->prophesize(Transformer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(EventStore::class)->willReturn($eventStore->reveal())->shouldBeCalled();
        $container->get(MessageConverter::class)->willReturn($messageConverter->reveal())->shouldBeCalled();
        $container->get(UrlHelper::class)->willReturn($urlHelper->reveal())->shouldBeCalled();
        $container->get(ResponseFactoryInterface::class)->willReturn($responseFactory->reveal())->shouldBeCalled();
        $container->get(JsonTransformer::class)->willReturn($transformer->reveal())->shouldBeCalled();

        $factory = new LoadStreamFactory();
        $stream = $factory->__invoke($container->reveal());

        $this->assertInstanceOf(LoadStream::class, $stream);
    }
}
