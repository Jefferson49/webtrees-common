<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2024 webtrees development team
 *                    <http://webtrees.net>
 *
 * Copyright (C) 2024 Markus Hemprich
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
 * An extension of the webtrees CaptchaService, which always returns to be no robot
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Fisharebest\Webtrees\Services\CaptchaService;
use Psr\Http\Message\ServerRequestInterface;


/**
 * An extension of the webtrees CaptchaService, which always returns to be no robot.
 * Can be used to call RequestHandlers from within the webtrees code without user interaction.
 */
class DeactivatedCaptchaService extends CaptchaService
{

    /**
     * Check the user's response.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function isRobot(ServerRequestInterface $request): bool
    {
        return false;
    }
}
