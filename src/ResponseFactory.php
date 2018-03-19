<?php
/**
 * This file is part of the proophsoftware/crm.
 * (c) 2018 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResponseFactory
{
    public function createJsonResponse(ServerRequestInterface $request, array $json): ResponseInterface;

    public function createEmptyResponse(ServerRequestInterface $request, int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface;

    public function createBadRequestResponse(ServerRequestInterface $request, string $reasonPhrase = ''): ResponseInterface;

    public function createUnsupportedMediaTypeResponse(ServerRequestInterface $request, string $reasonPhrase = ''): ResponseInterface;

    public function createNotFoundResponse(ServerRequestInterface $request): ResponseInterface;

    public function createInternalServerErrorResponse(ServerRequestInterface $request, string $reasonPhrase = ''): ResponseInterface;
}
