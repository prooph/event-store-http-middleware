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

use Interop\Http\Factory\ResponseFactoryInterface;
use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteProjection implements RequestHandlerInterface
{
    /**
     * @var ProjectionManager
     */
    private $projectionManager;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(ProjectionManager $projectionManager, ResponseFactoryInterface $responseFactory)
    {
        $this->projectionManager = $projectionManager;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $projectionName = \urldecode($request->getAttribute('name'));
        $deleteEmittedEvents = $request->getAttribute('deleteEmittedEvents');

        switch ($deleteEmittedEvents) {
            case 'false':
                $deleteEmittedEvents = false;
                break;
            case 'true':
                $deleteEmittedEvents = true;
                break;
        }

        try {
            $this->projectionManager->deleteProjection($projectionName, $deleteEmittedEvents);
        } catch (ProjectionNotFound $e) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->responseFactory->createResponse(204);
    }
}
