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

use ArrayIterator;
use DateTimeImmutable;
use DateTimeZone;
use EmptyIterator;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Http\Middleware\Action\LoadStream;
use Prooph\EventStore\Http\Middleware\GenericEvent;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Http\Middleware\UrlHelper;
use Prooph\EventStore\Metadata\FieldType;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\StreamName;
use Prophecy\Argument;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;

class LoadStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_description_when_invalid_accept_header_sent(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $uri = $this->prophesize(UriInterface::class);
        $uri->getScheme()->willReturn('http');
        $uri->getHost()->willReturn('localhost');
        $uri->getPort()->willReturn('8080');
        $uri->__toString()->willReturn('/stream');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo\bar')->shouldBeCalled();
        $request->getUri()->willReturn($uri->reveal())->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('EventStore::load', [
            'streamname' => \urlencode('foo\bar'),
        ])->willReturn($uri->reveal())->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $body = $this->prophesize(StreamInterface::class);
        $body->write(Argument::type('string'))->shouldBeCalled();
        $responsePrototype->getBody()->willReturn($body->reveal())->shouldBeCalled();
        $responsePrototype->withBody($body->reveal())->willReturn($responsePrototype->reveal())->shouldBeCalled();
        $responsePrototype->withAddedHeader('Content-Type', 'application/vnd.eventstore.streamdesc+json; charset=utf-8')
            ->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $transformer = $this->prophesize(Transformer::class);

        $action = new LoadStream($eventStore->reveal(), $messageConverter->reveal(), $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/vnd.eventstore.atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_bad_request(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('head')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('forward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('1')->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(400)->willReturn($responsePrototype)->shouldBeCalled();

        $transformer = $this->prophesize(Transformer::class);

        $action = new LoadStream($eventStore->reveal(), $messageConverter->reveal(), $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer($transformer->reveal(), 'application/vnd.eventstore.atom+json');

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_loads_events(): void
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $uuid3 = Uuid::uuid4()->toString();

        $time1 = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $time2 = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $time3 = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->load(new StreamName('foo'), 1, 3, new MetadataMatcher())->willReturn(new ArrayIterator([
            GenericEvent::fromArray([
                'uuid' => $uuid1,
                'message_name' => 'message_one',
                'created_at' => $time1,
                'payload' => ['one'],
                'metadata' => [],
            ]),
            GenericEvent::fromArray([
                'uuid' => $uuid2,
                'message_name' => 'message_two',
                'created_at' => $time2,
                'payload' => ['two'],
                'metadata' => [],
            ]),
            GenericEvent::fromArray([
                'uuid' => $uuid3,
                'message_name' => 'message_three',
                'created_at' => $time3,
                'payload' => ['three'],
                'metadata' => [],
            ]),
        ]))->shouldBeCalled();

        $messageConverter = new NoOpMessageConverter();

        $uri = $this->prophesize(UriInterface::class);
        $uri->getScheme()->willReturn('http')->shouldBeCalled();
        $uri->getPort()->willReturn(8080)->shouldBeCalled();
        $uri->getHost()->willReturn('localhost')->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('1')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('forward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('3')->shouldBeCalled();
        $request->getUri()->willReturn($uri->reveal())->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
        ])->willReturn('/stream/foo')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => '1',
            'direction' => 'forward',
            'count' => 3,
        ])->willReturn('/stream/foo/1/forward/3')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => 'head',
            'direction' => 'backward',
            'count' => 3,
        ])->willReturn('/stream/foo/head/backward/3')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), [
            'title' => 'Event stream \'foo\'',
            'id' => 'http://localhost:8080/stream/foo',
            'streamName' => 'foo',
            '_links' => [
                [
                    'uri' => 'http://localhost:8080/stream/foo',
                    'relation' => 'self',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/1/forward/3',
                    'relation' => 'first',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/head/backward/3',
                    'relation' => 'last',
                ],
            ],
            'entries' => [
                [
                    'message_name' => 'message_one',
                    'uuid' => $uuid1,
                    'payload' => [
                        0 => 'one',
                    ],
                    'metadata' => [],
                    'created_at' => $time1->format('Y-m-d\TH:i:s.u'),
                ],
                [
                    'message_name' => 'message_two',
                    'uuid' => $uuid2,
                    'payload' => [
                        0 => 'two',
                    ],
                    'metadata' => [],
                    'created_at' => $time2->format('Y-m-d\TH:i:s.u'),
                ],
                [
                    'message_name' => 'message_three',
                    'uuid' => $uuid3,
                    'payload' => [
                        0 => 'three',
                    ],
                    'metadata' => [],
                    'created_at' => $time3->format('Y-m-d\TH:i:s.u'),
                ],
            ],
        ])->willReturn($responsePrototype)->shouldBeCalled();

        $action = new LoadStream($eventStore->reveal(), $messageConverter, $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer(
            $transformer->reveal(),
            'application/vnd.eventstore.atom+json',
            'application/atom+json',
            'application/json'
        );

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_load_events_reverse(): void
    {
        $uuid1 = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $uuid3 = Uuid::uuid4()->toString();

        $time1 = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $time2 = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $time3 = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->loadReverse(new StreamName('foo'), 3, 3, new MetadataMatcher())->willReturn(new ArrayIterator([
            GenericEvent::fromArray([
                'uuid' => $uuid3,
                'message_name' => 'message_three',
                'created_at' => $time3,
                'payload' => ['three'],
                'metadata' => [],
            ]),
            GenericEvent::fromArray([
                'uuid' => $uuid2,
                'message_name' => 'message_two',
                'created_at' => $time2,
                'payload' => ['two'],
                'metadata' => [],
            ]),
            GenericEvent::fromArray([
                'uuid' => $uuid1,
                'message_name' => 'message_one',
                'created_at' => $time1,
                'payload' => ['one'],
                'metadata' => [],
            ]),
        ]))->shouldBeCalled();

        $messageConverter = new NoOpMessageConverter();

        $uri = $this->prophesize(UriInterface::class);
        $uri->getScheme()->willReturn('http')->shouldBeCalled();
        $uri->getPort()->willReturn(8080)->shouldBeCalled();
        $uri->getHost()->willReturn('localhost')->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('3')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('backward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('3')->shouldBeCalled();
        $request->getUri()->willReturn($uri->reveal())->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
        ])->willReturn('/stream/foo')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => '1',
            'direction' => 'forward',
            'count' => 3,
        ])->willReturn('/stream/foo/1/forward/3')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => 'head',
            'direction' => 'backward',
            'count' => 3,
        ])->willReturn('/stream/foo/head/backward/3')->shouldBeCalled();

        $expected = [
            'title' => 'Event stream \'foo\'',
            'id' => 'http://localhost:8080/stream/foo',
            'streamName' => 'foo',
            '_links' => [
                [
                    'uri' => 'http://localhost:8080/stream/foo',
                    'relation' => 'self',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/1/forward/3',
                    'relation' => 'first',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/head/backward/3',
                    'relation' => 'last',
                ],
            ],
            'entries' => [
                [
                    'message_name' => 'message_three',
                    'uuid' => $uuid3,
                    'payload' => [
                        0 => 'three',
                    ],
                    'metadata' => [],
                    'created_at' => $time3->format('Y-m-d\TH:i:s.u'),
                ],
                [
                    'message_name' => 'message_two',
                    'uuid' => $uuid2,
                    'payload' => [
                        0 => 'two',
                    ],
                    'metadata' => [],
                    'created_at' => $time2->format('Y-m-d\TH:i:s.u'),
                ],
                [
                    'message_name' => 'message_one',
                    'uuid' => $uuid1,
                    'payload' => [
                        0 => 'one',
                    ],
                    'metadata' => [],
                    'created_at' => $time1->format('Y-m-d\TH:i:s.u'),
                ],
            ],
        ];

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), $expected)->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new LoadStream($eventStore->reveal(), $messageConverter, $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer(
            $transformer->reveal(),
            'application/vnd.eventstore.atom+json',
            'application/atom+json',
            'application/json'
        );

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_return_400_status_code_on_empty_stream(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->load(new StreamName('foo'), 1, 3, new MetadataMatcher())->willReturn(new EmptyIterator());

        $messageConverter = $this->prophesize(MessageConverter::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('1')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('forward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('3')->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, '\'1\' is not a valid event number')->willReturn($responsePrototype->reveal())->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $transformer = $this->prophesize(Transformer::class);

        $action = new LoadStream($eventStore->reveal(), $messageConverter->reveal(), $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer(
            $transformer->reveal(),
            'application/vnd.eventstore.atom+json',
            'application/atom+json',
            'application/json'
        );

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_return_404_status_code_on_stream_not_found(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->load(new StreamName('foo'), 1, 3, new MetadataMatcher())->willThrow(new StreamNotFound());

        $messageConverter = $this->prophesize(MessageConverter::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('1')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('forward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('3')->shouldBeCalled();
        $request->getQueryParams()->willReturn([])->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(404)->willReturn($responsePrototype)->shouldBeCalled();

        $transformer = $this->prophesize(Transformer::class);

        $action = new LoadStream($eventStore->reveal(), $messageConverter->reveal(), $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer(
            $transformer->reveal(),
            'application/vnd.eventstore.atom+json',
            'application/atom+json',
            'application/json'
        );

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_only_matched_metadata_and_properties_on_load(): void
    {
        $uuid1 = Uuid::uuid4()->toString();

        $time1 = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar', FieldType::METADATA());
        $metadataMatcher = $metadataMatcher->withMetadataMatch('message_name', Operator::EQUALS(), 'message_one', FieldType::MESSAGE_PROPERTY());

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->load(new StreamName('foo'), 1, 3, $metadataMatcher)->willReturn(new ArrayIterator([
            GenericEvent::fromArray([
                'uuid' => $uuid1,
                'message_name' => 'message_one',
                'created_at' => $time1,
                'payload' => ['one'],
                'metadata' => [
                    'foo' => 'bar',
                ],
            ]),
        ]))->shouldBeCalled();

        $messageConverter = new NoOpMessageConverter();

        $uri = $this->prophesize(UriInterface::class);
        $uri->getScheme()->willReturn('http')->shouldBeCalled();
        $uri->getPort()->willReturn(8080)->shouldBeCalled();
        $uri->getHost()->willReturn('localhost')->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('1')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('forward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('3')->shouldBeCalled();
        $request->getUri()->willReturn($uri->reveal())->shouldBeCalled();
        $request->getQueryParams()->willReturn([
            'meta_0_field' => 'foo',
            'meta_0_operator' => 'EQUALS',
            'meta_0_value' => 'bar',
            'meta_1_field' => 'missing_parts',
            'meta_2_field' => 'invalid op',
            'meta_2_operator' => 'INVALID',
            'meta_2_value' => 'some value',
            'property_0_field' => 'message_name',
            'property_0_operator' => 'EQUALS',
            'property_0_value' => 'message_one',
            'property_1_field' => 'missing partts',
            'property_2_field' => 'invalid_op',
            'property_2_operator' => 'INVALID',
            'property_2_value' => 'some value',
        ])->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
        ])->willReturn('/stream/foo')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => '1',
            'direction' => 'forward',
            'count' => 3,
        ])->willReturn('/stream/foo/1/forward/3')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => 'head',
            'direction' => 'backward',
            'count' => 3,
        ])->willReturn('/stream/foo/head/backward/3')->shouldBeCalled();

        $expected = [
            'title' => 'Event stream \'foo\'',
            'id' => 'http://localhost:8080/stream/foo',
            'streamName' => 'foo',
            '_links' => [
                [
                    'uri' => 'http://localhost:8080/stream/foo',
                    'relation' => 'self',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/1/forward/3',
                    'relation' => 'first',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/head/backward/3',
                    'relation' => 'last',
                ],
            ],
            'entries' => [
                [
                    'message_name' => 'message_one',
                    'uuid' => $uuid1,
                    'payload' => [
                        0 => 'one',
                    ],
                    'metadata' => [
                        'foo' => 'bar',
                    ],
                    'created_at' => $time1->format('Y-m-d\TH:i:s.u'),
                ],
            ],
        ];

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), $expected)->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new LoadStream($eventStore->reveal(), $messageConverter, $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer(
            $transformer->reveal(),
            'application/vnd.eventstore.atom+json',
            'application/atom+json',
            'application/json'
        );

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_only_matched_metadata_and_properties_on_load_reverse(): void
    {
        $uuid1 = Uuid::uuid4()->toString();

        $time1 = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar', FieldType::METADATA());
        $metadataMatcher = $metadataMatcher->withMetadataMatch('message_name', Operator::EQUALS(), 'message_one', FieldType::MESSAGE_PROPERTY());

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->loadReverse(new StreamName('foo'), 3, 3, $metadataMatcher)->willReturn(new ArrayIterator([
            GenericEvent::fromArray([
                'uuid' => $uuid1,
                'message_name' => 'message_one',
                'created_at' => $time1,
                'payload' => ['one'],
                'metadata' => [
                    'foo' => 'bar',
                ],
            ]),
        ]))->shouldBeCalled();

        $messageConverter = new NoOpMessageConverter();

        $uri = $this->prophesize(UriInterface::class);
        $uri->getScheme()->willReturn('http')->shouldBeCalled();
        $uri->getPort()->willReturn(8080)->shouldBeCalled();
        $uri->getHost()->willReturn('localhost')->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Accept')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('foo')->shouldBeCalled();
        $request->getAttribute('start')->willReturn('3')->shouldBeCalled();
        $request->getAttribute('direction')->willReturn('backward')->shouldBeCalled();
        $request->getAttribute('count')->willReturn('3')->shouldBeCalled();
        $request->getUri()->willReturn($uri->reveal())->shouldBeCalled();
        $request->getQueryParams()->willReturn([
            'meta_0_field' => 'foo',
            'meta_0_operator' => 'EQUALS',
            'meta_0_value' => 'bar',
            'meta_1_field' => 'missing_parts',
            'meta_2_field' => 'invalid op',
            'meta_2_operator' => 'INVALID',
            'meta_2_value' => 'some value',
            'property_0_field' => 'message_name',
            'property_0_operator' => 'EQUALS',
            'property_0_value' => 'message_one',
            'property_1_field' => 'missing partts',
            'property_2_field' => 'invalid_op',
            'property_2_operator' => 'INVALID',
            'property_2_value' => 'some value',
        ])->shouldBeCalled();

        $urlHelper = $this->prophesize(UrlHelper::class);
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
        ])->willReturn('/stream/foo')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => '1',
            'direction' => 'forward',
            'count' => 3,
        ])->willReturn('/stream/foo/1/forward/3')->shouldBeCalled();
        $urlHelper->generate('EventStore::load', [
            'streamname' => 'foo',
            'start' => 'head',
            'direction' => 'backward',
            'count' => 3,
        ])->willReturn('/stream/foo/head/backward/3')->shouldBeCalled();

        $expected = [
            'title' => 'Event stream \'foo\'',
            'id' => 'http://localhost:8080/stream/foo',
            'streamName' => 'foo',
            '_links' => [
                [
                    'uri' => 'http://localhost:8080/stream/foo',
                    'relation' => 'self',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/1/forward/3',
                    'relation' => 'first',
                ],
                [
                    'uri' => 'http://localhost:8080/stream/foo/head/backward/3',
                    'relation' => 'last',
                ],
            ],
            'entries' => [
                [
                    'message_name' => 'message_one',
                    'uuid' => $uuid1,
                    'payload' => [
                        0 => 'one',
                    ],
                    'metadata' => [
                        'foo' => 'bar',
                    ],
                    'created_at' => $time1->format('Y-m-d\TH:i:s.u'),
                ],
            ],
        ];

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $transformer = $this->prophesize(Transformer::class);
        $transformer->createResponse($responseFactory->reveal(), $expected)->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $action = new LoadStream($eventStore->reveal(), $messageConverter, $urlHelper->reveal(), $responseFactory->reveal());
        $action->addTransformer(
            $transformer->reveal(),
            'application/vnd.eventstore.atom+json',
            'application/atom+json',
            'application/json'
        );

        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
