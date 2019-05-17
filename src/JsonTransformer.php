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

namespace Prooph\EventStore\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Prooph\EventStore\Http\Middleware\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

final class JsonTransformer implements Transformer
{
    /**
     * Default flags for json_encode; value of:
     *
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     *
     * @const int
     */
    private const DEFAULT_JSON_FLAGS = 79;

    /**
     * @param array $result
     * @return ResponseInterface
     */
    public function createResponse(ResponseFactoryInterface $factory, array $result): ResponseInterface
    {
        $response = $factory->createResponse();

        $json = $this->jsonEncode($result, self::DEFAULT_JSON_FLAGS);

        $body = $response->getBody();
        $body->write($json);

        return $response->withAddedHeader('Content-Type', 'application/json')->withBody($body);
    }

    /**
     * Encode the provided data to JSON.
     *
     * @param mixed $data
     * @param int $encodingOptions
     * @return string
     * @throws InvalidArgumentException if unable to encode the $data to JSON.
     */
    private function jsonEncode(array $data, $encodingOptions): string
    {
        // Clear json_last_error()
        \json_encode(null);

        $json = \json_encode($data, $encodingOptions);

        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException(\sprintf(
                'Unable to encode data to JSON in %s: %s',
                __CLASS__,
                \json_last_error_msg()
            ));
        }

        return $json;
    }
}
