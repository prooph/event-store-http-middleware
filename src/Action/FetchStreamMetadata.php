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

use Psr\Http\Message\ResponseFactoryInterface;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\ReadOnlyEventStore;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FetchStreamMetadata implements RequestHandlerInterface
{
    /**
     * @var ReadOnlyEventStore
     */
    private $eventStore;

    /**
     * @var Transformer[]
     */
    private $transformers = [];

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(ReadOnlyEventStore $eventStore, ResponseFactoryInterface $responseFactory)
    {
        $this->eventStore = $eventStore;
        $this->responseFactory = $responseFactory;
    }

    public function addTransformer(Transformer $transformer, string ...$names)
    {
        foreach ($names as $name) {
            $this->transformers[$name] = $transformer;
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! \array_key_exists($request->getHeaderLine('Accept'), $this->transformers)) {
            return $this->responseFactory->createResponse(415);
        }

        $streamName = \urldecode($request->getAttribute('streamname'));

        try {
            $metadata = $this->eventStore->fetchStreamMetadata(new StreamName($streamName));
        } catch (StreamNotFound $e) {
            return $this->responseFactory->createResponse(404);
        }

        $transformer = $this->transformers[$request->getHeaderLine('Accept')];

        return $transformer->createResponse($this->responseFactory, $metadata);
    }
}
