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

use Prooph\EventStore\Http\Middleware\ResponseFactory;
use Prooph\EventStore\ReadOnlyEventStore;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HasStream implements RequestHandlerInterface
{
    /**
     * @var ReadOnlyEventStore
     */
    private $eventStore;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ReadOnlyEventStore $eventStore, ResponseFactory $responseFactory)
    {
        $this->eventStore = $eventStore;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $streamName = urldecode($request->getAttribute('streamname'));

        if ($this->eventStore->hasStream(new StreamName($streamName))) {
            return $this->responseFactory->createEmptyResponse($request, 200);
        }

        return $this->responseFactory->createNotFoundResponse($request);
    }
}
