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

namespace Prooph\EventStore\Http\Middleware\Action;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseFactoryInterface;
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
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(EventStore $eventStore, ResponseFactoryInterface $responseFactory)
    {
        $this->eventStore = $eventStore;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $streamName = \urldecode($request->getAttribute('streamname'));

        if (! \in_array($request->getHeaderLine('Content-Type'), $this->validRequestContentTypes)) {
            return $this->responseFactory->createResponse(415);
        }

        $metadata = $request->getParsedBody();

        try {
            $this->eventStore->updateStreamMetadata(new StreamName($streamName), $metadata);
        } catch (StreamNotFound $e) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->responseFactory->createResponse(204);
    }
}
