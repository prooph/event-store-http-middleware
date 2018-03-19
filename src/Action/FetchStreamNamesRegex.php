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

use Prooph\EventStore\Http\Middleware\Model\MetadataMatcherBuilder;
use Prooph\EventStore\Http\Middleware\ResponseFactory;
use Prooph\EventStore\ReadOnlyEventStore;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FetchStreamNamesRegex implements RequestHandlerInterface
{
    private const DEFAULT_LIMIT = 20;
    private const DEFAULT_OFFSET = 0;

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

        $filter = urldecode($request->getAttribute('filter'));

        $queryParams = $request->getQueryParams();

        $limit = $queryParams['limit'] ?? self::DEFAULT_LIMIT;
        $offset = $queryParams['offset'] ?? self::DEFAULT_OFFSET;

        $metadataMatcherBuilder = new MetadataMatcherBuilder();
        $metadataMatcher = $metadataMatcherBuilder->createMetadataMatcherFrom($request, false);

        $streamNames = $this->eventStore->fetchStreamNamesRegex($filter, $metadataMatcher, (int) $limit, (int) $offset);

        $streamNames = array_map(
            function (StreamName $streamName): string {
                return $streamName->toString();
            },
            $streamNames
        );

        return $this->responseFactory->createJsonResponse($request, $streamNames);
    }
}
