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

use Interop\Http\Factory\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\FetchCategoryNamesRegex;
use Prooph\EventStore\Http\Middleware\Transformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FetchCategoryNamesRegexTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_415_when_invalid_accept_header_sent(): void
    {
        $eventStore = $this->prophesize(EventStore::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(415)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new FetchCategoryNamesRegex($eventStore->reveal(), $responseFactory->reveal());
        $action->addTransformer(new TransformerStub($responsePrototype->reveal()), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_filtered_category_names(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore
            ->fetchCategoryNamesRegex('^foo$', 20, 0)
            ->willReturn(['foo'])
            ->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/atom+json')->shouldBeCalled();
        $request->getAttribute('filter')->willReturn(\urlencode('^foo$'))->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $transformer = $this->prophesize(Transformer::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer->createResponse($responseFactory->reveal(), ['foo'])->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new FetchCategoryNamesRegex($eventStore->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
