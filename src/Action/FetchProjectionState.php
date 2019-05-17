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
use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FetchProjectionState implements RequestHandlerInterface
{
    /**
     * @var ProjectionManager
     */
    private $projectionManager;

    /**
     * @var Transformer[]
     */
    private $transformers = [];

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(ProjectionManager $projectionManager, ResponseFactoryInterface $responseFactory)
    {
        $this->projectionManager = $projectionManager;
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

        $name = $request->getAttribute('name');

        try {
            $state = $this->projectionManager->fetchProjectionState($name);
        } catch (ProjectionNotFound $e) {
            return $this->responseFactory->createResponse(404);
        }

        $transformer = $this->transformers[$request->getHeaderLine('Accept')];

        return $transformer->createResponse($this->responseFactory, $state);
    }
}
