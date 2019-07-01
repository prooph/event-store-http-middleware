<?php

/**
 * This file is part of prooph/event-store-http-middleware.
 * (c) 2018-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2018-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Http\Middleware\Action;

use Prooph\EventStore\Http\Middleware\Transformer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class TransformerStub implements Transformer
{
    /**
     * @var ResponseInterface
     */
    private $result;

    /**
     * @param ResponseInterface $result
     */
    public function __construct(ResponseInterface $result)
    {
        $this->result = $result;
    }

    /**
     * @param ResponseFactoryInterface $factory
     * @param array $result
     * @return ResponseInterface
     */
    public function createResponse(ResponseFactoryInterface $factory, array $result): ResponseInterface
    {
        return $this->result;
    }
}
