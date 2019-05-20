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

namespace ProophTest\EventStore\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Http\Middleware\Exception\InvalidArgumentException;
use Prooph\EventStore\Http\Middleware\JsonTransformer;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class JsonTransformerTest extends TestCase
{
    /**
     * @var JsonTransformer
     */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new JsonTransformer();
    }

    /**
     * @test
     */
    public function it_provides_a_json_response()
    {
        $expectedJson = \json_encode(['foo' => 'bar']);

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $body = $this->prophesize(StreamInterface::class);
        $body->write(Argument::exact($expectedJson))->shouldBeCalled();
        $responsePrototype->getBody()->willReturn($body->reveal())->shouldBeCalled();
        $responsePrototype->withBody($body->reveal())->willReturn($responsePrototype->reveal())->shouldBeCalled();
        $responsePrototype->withAddedHeader('Content-Type', 'application/json')
            ->willReturn($responsePrototype->reveal())->shouldBeCalled();

        $this->transformer->createResponse($responseFactory->reveal(), ['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_if_result_cannot_be_json_encoded()
    {
        //JSON encode only works with utf-8 encoding
        $wrongEncodedResult = ['foo' => \mb_convert_encoding('üäö', 'ISO-8859-1')];

        $responsePrototype = $this->prophesize(ResponseInterface::class);
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $responseFactory->createResponse()->willReturn($responsePrototype)->shouldBeCalled();

        $this->expectException(InvalidArgumentException::class);

        $this->transformer->createResponse($responseFactory->reveal(), $wrongEncodedResult);
    }
}
