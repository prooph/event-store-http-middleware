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

namespace ProophTest\EventStore\Http\Middleware;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Http\Middleware\GenericEventFactory;
use Ramsey\Uuid\Uuid;

class GenericEventFactoryTest extends TestCase
{
    /**
     * @var GenericEventFactory
     */
    private $messageFactory;

    protected function setUp()
    {
        $this->messageFactory = new GenericEventFactory();
    }

    /**
     * @test
     */
    public function it_creates_a_new_message_from_array(): void
    {
        $uuid = Uuid::uuid4();
        $createdAt = new \DateTimeImmutable();

        $event = $this->messageFactory->createMessageFromArray('happened', [
            'uuid' => $uuid->toString(),
            'payload' => ['command' => 'payload'],
            'metadata' => ['command' => 'metadata'],
            'created_at' => $createdAt,
        ]);

        $this->assertEquals('happened', $event->messageName());
        $this->assertEquals($uuid->toString(), $event->uuid()->toString());
        $this->assertEquals($createdAt, $event->createdAt());
        $this->assertEquals(['command' => 'payload'], $event->payload());
        $this->assertEquals(['command' => 'metadata'], $event->metadata());
    }

    /**
     * @test
     */
    public function it_creates_a_new_message_with_defaults_from_array(): void
    {
        $event = $this->messageFactory->createMessageFromArray('happened', [
            'payload' => ['command' => 'payload'],
        ]);

        $this->assertEquals('happened', $event->messageName());
        $this->assertEquals(['command' => 'payload'], $event->payload());
        $this->assertEquals([], $event->metadata());
        $this->assertEquals(Message::TYPE_EVENT, $event->messageType());
    }
}
