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

use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Projection\ProjectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FetchProjectionNames implements RequestHandlerInterface
{
    private const DEFAULT_LIMIT = 20;
    private const DEFAULT_OFFSET = 0;

    /**
     * @var ProjectionManager
     */
    private $projectionManager;

    /**
     * @var Transformer[]
     */
    private $transformers = [];

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    public function __construct(ProjectionManager $projectionManager, ResponseInterface $responsePrototype)
    {
        $this->projectionManager = $projectionManager;
        $this->responsePrototype = $responsePrototype;
    }

    public function addTransformer(Transformer $transformer, string ...$names)
    {
        foreach ($names as $name) {
            $this->transformers[$name] = $transformer;
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! array_key_exists($request->getHeaderLine('Accept'), $this->transformers)) {
            return $this->responsePrototype->withStatus(415);
        }

        $filter = $request->getAttribute('filter');

        if (null !== $filter) {
            $filter = urldecode($filter);
        }

        $queryParams = $request->getQueryParams();

        $limit = $queryParams['limit'] ?? self::DEFAULT_LIMIT;
        $offset = $queryParams['offset'] ?? self::DEFAULT_OFFSET;

        $projectionNames = $this->projectionManager->fetchProjectionNames($filter, (int) $limit, (int) $offset);

        $transformer = $this->transformers[$request->getHeaderLine('Accept')];

        return $transformer->createResponse($projectionNames);
    }
}
