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

namespace Prooph\EventStore\Http\Middleware\Action;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateStreamMetadata implements RequestHandlerInterface
{
    /**
     * @var EventStore
     */
    private $eventStore;

    private $validRequestContentTypes = [
        'application/vnd.eventstore.atom+json',
        'application/json',
        'application/atom+json',
    ];

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    public function __construct(EventStore $eventStore, ResponseInterface $responsePrototype)
    {
        $this->eventStore = $eventStore;
        $this->responsePrototype = $responsePrototype;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $streamName = urldecode($request->getAttribute('streamname'));

        if (! in_array($request->getHeaderLine('Content-Type'), $this->validRequestContentTypes)) {
            return $this->responsePrototype->withStatus(415);
        }

        $metadata = $request->getParsedBody();

        try {
            $this->eventStore->updateStreamMetadata(new StreamName($streamName), $metadata);
        } catch (StreamNotFound $e) {
            return $this->responsePrototype->withStatus(404);
        }

        return $this->responsePrototype->withStatus(204);
    }
}
