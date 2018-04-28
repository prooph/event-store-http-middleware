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

use Interop\Http\Factory\ResponseFactoryInterface;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Http\Middleware\Model\MetadataMatcherBuilder;
use Prooph\EventStore\Http\Middleware\Transformer;
use Prooph\EventStore\Http\Middleware\UrlHelper;
use Prooph\EventStore\ReadOnlyEventStore;
use Prooph\EventStore\StreamName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LoadStream implements RequestHandlerInterface
{
    /**
     * @var ReadOnlyEventStore
     */
    private $eventStore;

    /**
     * @var MessageConverter
     */
    private $messageConverter;

    /**
     * @var Transformer[]
     */
    private $transformers = [];

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(
        ReadOnlyEventStore $eventStore,
        MessageConverter $messageConverter,
        UrlHelper $urlHelper,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->eventStore = $eventStore;
        $this->messageConverter = $messageConverter;
        $this->urlHelper = $urlHelper;
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
        $streamName = urldecode($request->getAttribute('streamname'));

        if (! array_key_exists($request->getHeaderLine('Accept'), $this->transformers)) {
            return $this->returnDescription($request, $streamName);
        }

        $transformer = $this->transformers[$request->getHeaderLine('Accept')];

        $start = $request->getAttribute('start');

        if ('head' === $start) {
            $start = PHP_INT_MAX;
        }

        $start = (int) $start;

        $count = (int) $request->getAttribute('count');

        $direction = $request->getAttribute('direction');

        if (PHP_INT_MAX === $start && 'forward' === $direction) {
            return $this->responseFactory->createResponse(400);
        }

        $metadataMatcherBuilder = new MetadataMatcherBuilder();
        $metadataMatcher = $metadataMatcherBuilder->createMetadataMatcherFrom($request, true);

        try {
            if ($direction === 'backward') {
                $streamEvents = $this->eventStore->loadReverse(new StreamName($streamName), $start, $count, $metadataMatcher);
            } else {
                $streamEvents = $this->eventStore->load(new StreamName($streamName), $start, $count, $metadataMatcher);
            }
        } catch (StreamNotFound $e) {
            return $this->responseFactory->createResponse(404);
        }

        if (! $streamEvents->valid()) {
            return $this->responseFactory->createResponse()->withStatus(400, '\'' . $start . '\' is not a valid event number');
        }

        $entries = [];

        foreach ($streamEvents as $event) {
            $entry = $this->messageConverter->convertToArray($event);
            $entry['created_at'] = $entry['created_at']->format('Y-m-d\TH:i:s.u');
            $entries[] = $entry;
        }

        $host = $this->host($request);

        $id = $host . $this->urlHelper->generate('EventStore::load', [
                'streamname' => urlencode($streamName),
            ]);

        $result = [
            'title' => "Event stream '$streamName'",
            'id' => $id,
            'streamName' => $streamName,
            '_links' => [
                [
                    'uri' => $id,
                    'relation' => 'self',
                ],
                [
                    'uri' => $host . $this->urlHelper->generate('EventStore::load', [
                            'streamname' => urlencode($streamName),
                            'start' => '1',
                            'direction' => 'forward',
                            'count' => $count,
                        ]),
                    'relation' => 'first',
                ],
                [
                    'uri' => $host . $this->urlHelper->generate('EventStore::load', [
                            'streamname' => urlencode($streamName),
                            'start' => 'head',
                            'direction' => 'backward',
                            'count' => $count,
                        ]),
                    'relation' => 'last',
                ],
            ],
            'entries' => $entries,
        ];

        return $transformer->createResponse($this->responseFactory, $result);
    }

    private function returnDescription(ServerRequestInterface $request, string $streamName): ResponseInterface
    {
        $id = $this->host($request) . $this->urlHelper->generate('EventStore::load', [
            'streamname' => urlencode($streamName),
        ]);

        $response = $this->responseFactory->createResponse();

        $body = $response->getBody();
        $body->write(json_encode([
            'title' => 'Description document for \'' . $streamName . '\'',
            'description' => 'The description document will be presented when no accept header is present or it was requested',
            '_links' => [
                'self' => [
                    'href' => $id,
                    'supportedContentTypes' => [
                        'application/vnd.eventstore.streamdesc+json',
                    ],
                ],
                'stream' => [
                    'href' => $id,
                    'supportedContentTypes' => array_keys($this->transformers),
                ],
            ],
        ]));

        return $response->withAddedHeader('Content-Type', 'application/vnd.eventstore.streamdesc+json; charset=utf-8')
            ->withBody($body);
    }

    private function host(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $host = $uri->getScheme() . '://' . $uri->getHost();

        if (null !== $uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        return $host;
    }
}
