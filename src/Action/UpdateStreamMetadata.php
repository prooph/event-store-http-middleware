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
use Prooph\EventStore\Http\Middleware\ResponseFactory;
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
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(EventStore $eventStore, ResponseFactory $responseFactory)
    {
        $this->eventStore = $eventStore;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $streamName = urldecode($request->getAttribute('streamname'));

        if (! in_array($request->getHeaderLine('Content-Type'), $this->validRequestContentTypes)) {
            return $this->responseFactory->createUnsupportedMediaTypeResponse($request);
        }

        $metadata = $request->getParsedBody();

        try {
            $this->eventStore->updateStreamMetadata(new StreamName($streamName), $metadata);
        } catch (StreamNotFound $e) {
            return $this->responseFactory->createNotFoundResponse($request);
        }

        return $this->responseFactory->createEmptyResponse($request, 204);
    }
}
