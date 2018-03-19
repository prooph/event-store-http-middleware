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

use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Http\Middleware\ResponseFactory;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FetchProjectionStreamPositions implements RequestHandlerInterface
{
    /**
     * @var ProjectionManager
     */
    private $projectionManager;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ProjectionManager $projectionManager, ResponseFactory $responseFactory)
    {
        $this->projectionManager = $projectionManager;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name');

        try {
            $streamPositions = $this->projectionManager->fetchProjectionStreamPositions($name);
        } catch (ProjectionNotFound $e) {
            return $this->responseFactory->createNotFoundResponse($request);
        }

        return $this->responseFactory->createJsonResponse($request, $streamPositions);
    }
}
