<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2026 webtrees development team
 *                    <http://webtrees.net>
 *
 * Copyright (C) 2026 Markus Hemprich
 *                    <http://www.familienforschung-hemprich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 *
 * A middleware to ensure the request uses the HTTP POST method
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Http\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class RequireHttpPostMethod implements MiddlewareInterface
{
    /**
     * Only allow access to the route if it uses the HTTP POST method
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Request does not use the HTTP POST method?
        if ($request->getMethod() !== RequestMethodInterface::METHOD_POST) {

            // Code from: Fisharebest\Webtrees\Http\Middleware\InvokeController
            // Last check: 2026-11-04
            return Registry::responseFactory()->response('', StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, [
                'Allow' => RequestMethodInterface::METHOD_POST,
            ]);
        }

        // Continue processing if the request uses the HTTP POST method
        return $handler->handle($request);
    }
}
