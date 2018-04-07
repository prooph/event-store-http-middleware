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

use ArrayIterator;
use DateTimeImmutable;
use DateTimeZone;
use Interop\Http\Factory\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Http\Middleware\Action\PostStream;
use Prooph\EventStore\Http\Middleware\GenericEvent;
use Prooph\EventStore\Http\Middleware\GenericEventFactory;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalEventStore;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class PostStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_415_on_invalid_content_type(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('text/html')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(415)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_request_body(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn(['invalid body'])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Write request body invalid')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_request_body_2(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn('invalid')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Write request body invalid')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_creates_missing_event_uuid(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(true)->shouldBeCalled();
        $eventStore->appendTo(
            new StreamName('test-stream'),
            Argument::any()
        )->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'message_name' => 'foo',
            'payload' => [],
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_event_uuid(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => 'invalid',
            'payload' => [],
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Invalid event uuid provided')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_missing_event_name(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'payload' => [],
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Empty event name provided')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_event_name(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => '',
            'payload' => [],
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Invalid event name provided')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_creates_missing_event_payload(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(true)->shouldBeCalled();
        $eventStore->appendTo(
            new StreamName('test-stream'),
            Argument::any()
        )->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => 'event one',
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_event_payload(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => 'event one',
            'payload' => 'foo',
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Invalid event payload provided')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_creates_missing_event_metadata(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(true)->shouldBeCalled();
        $eventStore->appendTo(
            new StreamName('test-stream'),
            Argument::any()
        )->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => 'event one',
            'payload' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_event_metadata(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => 'foo',
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Invalid event metadata provided')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_on_invalid_event_created_at(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
            'created_at' => 'invalid',

        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Invalid created at provided, expected format: Y-m-d\TH:i:s.u')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_returns_400_when_event_could_not_be_instantiated(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(EventStore::class);

        $messageFactory = $this->prophesize(MessageFactory::class);
        $messageFactory->createMessageFromArray('event one', [
            'uuid' => $uuid,
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
            'created_at' => $time,
        ])->willThrow(new RuntimeException());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => $uuid,
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
        ]])->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(400, 'Could not create event instance')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory->reveal(), $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_rolls_back_transaction_on_error_using_transactional_event_store(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);
        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(false)->shouldBeCalled();
        $eventStore->create(new StreamName('test-stream'), Argument::type(ArrayIterator::class))->willThrow(new RuntimeException());
        $eventStore->rollback()->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => Uuid::uuid4()->toString(),
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
        ]])->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responsePrototype->withStatus(500, 'Cannot create or append to stream')->willReturn($responsePrototype)->shouldBeCalled();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_creates_stream_using_transactional_event_store(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(TransactionalEventStore::class);
        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(false)->shouldBeCalled();
        $eventStore->create(
            new Stream(
                new StreamName('test-stream'),
                new ArrayIterator([
                    GenericEvent::fromArray([
                        'uuid' => $uuid,
                        'message_name' => 'event one',
                        'payload' => [],
                        'metadata' => [],
                        'created_at' => $time,
                    ]),
                ]),
                []
            )
        )->shouldBeCalled();
        $eventStore->commit()->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => $uuid,
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
            'created_at' => $time->format('Y-m-d\TH:i:s.u'),
        ]])->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_appends_to_stream_using_transactional_event_store(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(TransactionalEventStore::class);
        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(true)->shouldBeCalled();
        $eventStore->appendTo(
            new StreamName('test-stream'),
            new ArrayIterator([
                GenericEvent::fromArray([
                    'uuid' => $uuid,
                    'message_name' => 'event one',
                    'payload' => [],
                    'metadata' => [],
                    'created_at' => $time,
                ]),
            ])
        )->shouldBeCalled();
        $eventStore->commit()->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => $uuid,
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
            'created_at' => $time->format('Y-m-d\TH:i:s.u'),
        ]])->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_creates_stream_using_non_transactional_event_store(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(false)->shouldBeCalled();
        $eventStore->create(
            new Stream(
                new StreamName('test-stream'),
                new ArrayIterator([
                    GenericEvent::fromArray([
                        'uuid' => $uuid,
                        'message_name' => 'event one',
                        'payload' => [],
                        'metadata' => [],
                        'created_at' => $time,
                    ]),
                ]),
                []
            )
        )->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => $uuid,
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
            'created_at' => $time->format('Y-m-d\TH:i:s.u'),
        ]])->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function it_appends_to_stream_using_non_transactional_event_store(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->hasStream(new StreamName('test-stream'))->willReturn(true)->shouldBeCalled();
        $eventStore->appendTo(
            new StreamName('test-stream'),
            new ArrayIterator([
                GenericEvent::fromArray([
                    'uuid' => $uuid,
                    'message_name' => 'event one',
                    'payload' => [],
                    'metadata' => [],
                    'created_at' => $time,
                ]),
            ])
        )->shouldBeCalled();

        $messageFactory = new GenericEventFactory();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Content-Type')->willReturn('application/vnd.eventstore.atom+json')->shouldBeCalled();
        $request->getParsedBody()->willReturn([[
            'uuid' => $uuid,
            'message_name' => 'event one',
            'payload' => [],
            'metadata' => [],
            'created_at' => $time->format('Y-m-d\TH:i:s.u'),
        ]])->shouldBeCalled();
        $request->getAttribute('streamname')->willReturn('test-stream')->shouldBeCalled();

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(204)->willReturn($responsePrototype)->shouldBeCalled();

        $action = new PostStream($eventStore->reveal(), $messageFactory, $responseFactory->reveal());
        $response = $action->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
