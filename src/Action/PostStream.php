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

namespace Prooph\EventStore\Http\Middleware\Action;

use ArrayIterator;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseFactoryInterface;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalEventStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final class PostStream implements RequestHandlerInterface
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    private $validRequestContentTypes = [
        'application/vnd.eventstore.atom+json',
        'application/json',
        'application/atom+json',
    ];

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(EventStore $eventStore, MessageFactory $messageFactory, ResponseFactoryInterface $responseFactory)
    {
        $this->eventStore = $eventStore;
        $this->messageFactory = $messageFactory;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! \in_array($request->getHeaderLine('Content-Type'), $this->validRequestContentTypes)) {
            return $this->responseFactory->createResponse(415);
        }

        $readEvents = $request->getParsedBody();

        if (! \is_array($readEvents) || empty($readEvents)) {
            return $this->responseFactory->createResponse()->withStatus(400, 'Write request body invalid');
        }

        $events = [];

        foreach ($readEvents as $event) {
            if (! \is_array($event)) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Write request body invalid');
            }

            if (! isset($event['uuid'])) {
                $event['uuid'] = Uuid::uuid4()->toString();
            }

            if (! \is_string($event['uuid']) || ! Uuid::isValid($event['uuid'])) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Invalid event uuid provided');
            }

            if (! isset($event['message_name'])) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Empty event name provided');
            }

            if (! \is_string($event['message_name']) || \strlen($event['message_name']) === 0) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Invalid event name provided');
            }

            if (! isset($event['payload'])) {
                $event['payload'] = [];
            }

            try {
                MessageDataAssertion::assertPayload($event['payload']);
            } catch (Throwable $e) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Invalid event payload provided');
            }

            if (! isset($event['metadata'])) {
                $event['metadata'] = [];
            }

            try {
                MessageDataAssertion::assertMetadata($event['metadata']);
            } catch (Throwable $e) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Invalid event metadata provided');
            }

            if (! isset($event['created_at'])) {
                $event['created_at'] = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            } else {
                $event['created_at'] = DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i:s.u',
                    $event['created_at'],
                    new DateTimeZone('UTC')
                );
            }

            if (! $event['created_at'] instanceof DateTimeImmutable) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Invalid created at provided, expected format: Y-m-d\TH:i:s.u');
            }

            try {
                $events[] = $this->messageFactory->createMessageFromArray($event['message_name'], $event);
            } catch (Throwable $e) {
                return $this->responseFactory->createResponse()->withStatus(400, 'Could not create event instance');
            }
        }

        $streamName = new StreamName(\urldecode($request->getAttribute('streamname')));

        if ($this->eventStore instanceof TransactionalEventStore) {
            $this->eventStore->beginTransaction();
        }

        try {
            if ($this->eventStore->hasStream($streamName)) {
                $this->eventStore->appendTo($streamName, new ArrayIterator($events));
            } else {
                $this->eventStore->create(new Stream($streamName, new ArrayIterator($events)));
            }
        } catch (Throwable $e) {
            if ($this->eventStore instanceof TransactionalEventStore) {
                $this->eventStore->rollback();
            }

            return $this->responseFactory->createResponse()->withStatus(500, 'Cannot create or append to stream');
        }

        if ($this->eventStore instanceof TransactionalEventStore) {
            $this->eventStore->commit();
        }

        return $this->responseFactory->createResponse(204);
    }
}
