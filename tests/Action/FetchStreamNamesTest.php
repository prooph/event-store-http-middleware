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

namespace ProophTest\EventStore\Http\Middleware\Action;

use Psr\Http\Message\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\FetchStreamNames;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Metadata\FieldType;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FetchStreamNamesTest extends TestCase
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

        $transformer = $this->prophesize(Transformer::class);

        $action = new FetchStreamNames($eventStore->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_filtered_stream_names(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore
            ->fetchStreamNames('foo', new MetadataMatcher(), 20, 0)
            ->willReturn([new StreamName('foo'), new StreamName('foobar')])
            ->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/atom+json')->shouldBeCalled();
        $request->getAttribute('filter')->willReturn('foo')->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), ['foo', 'foobar'])->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new FetchStreamNames($eventStore->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_will_return_all_stream_names_without_filter(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore
            ->fetchStreamNames(null, new MetadataMatcher(), 20, 0)
            ->willReturn([new StreamName('foo'), new StreamName('foobar')])
            ->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/atom+json')->shouldBeCalled();
        $request->getAttribute('filter')->willReturn(null)->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), ['foo', 'foobar'])->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new FetchStreamNames($eventStore->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_respects_given_metadata_in_query_params(): void
    {
        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar', FieldType::METADATA());

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore
            ->fetchStreamNames(null, $metadataMatcher, 20, 0)
            ->willReturn([new StreamName('foo'), new StreamName('foobar')])
            ->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/atom+json')->shouldBeCalled();
        $request->getAttribute('filter')->willReturn(null)->shouldBeCalled();
        $request->getQueryParams()->willReturn([
            'meta_0_field' => 'foo',
            'meta_0_operator' => 'EQUALS',
            'meta_0_value' => 'bar',
            'meta_1_field' => 'missing_parts',
            'meta_2_field' => 'invalid op',
            'meta_2_operator' => 'INVALID',
            'meta_2_value' => 'some value',
        ])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), ['foo', 'foobar'])->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new FetchStreamNames($eventStore->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
