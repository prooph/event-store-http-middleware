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

final class FetchProjectionStatus implements RequestHandlerInterface
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
        $name = $request->getAttribute('name');

        try {
            $status = $this->projectionManager->fetchProjectionStatus($name);
        } catch (ProjectionNotFound $e) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->responseFactory->createResponse()->withStatus(200, $status->getName());
    }
}
