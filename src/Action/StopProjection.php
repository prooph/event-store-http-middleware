<?php
/**
 * This file is part of the prooph/event-store-http-middleware.
 * (c) 2016-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Http\Middleware\Action;

use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class StopProjection implements RequestHandlerInterface
{
    /**
     * @var ProjectionManager
     */
    private $projectionManager;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    public function __construct(ProjectionManager $projectionManager, ResponseInterface $responsePrototype)
    {
        $this->projectionManager = $projectionManager;
        $this->responsePrototype = $responsePrototype;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $projectionName = urldecode($request->getAttribute('name'));

        try {
            $this->projectionManager->stopProjection($projectionName);
        } catch (ProjectionNotFound $e) {
            return $this->responsePrototype->withStatus(404);
        }

        return $this->responsePrototype->withStatus(204);
    }
}