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
 * Codeberg services to be used in webtrees custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Jefferson49\Webtrees\Exceptions\CodebergCommunicationError;


/**
 * A service to connect with Codeberg and request its Forgejo API.
 */
class CodebergService extends PlatformService
{
    const string NAME         = 'Codeberg';
    const string PLATFORM_URL = 'https://codeberg.org/';
    const string API_URL      = 'https://codeberg.org/api/v1';


    /**
     * Get the name of the hosting platform
     */
    public static function getPlatformName(): string {
        return self::NAME;
    }

    /**
     * Get the platform URL of the hosting platform
     */
    public static function getPlatformUrl(): string {
        return self::PLATFORM_URL;
    }

    /**
     * Get the API URL of the hosting platform
     */
    public static function getApiUrl(): string {
        return self::API_URL;
    }

    /**
     * Get the class for an communication error exception
     */
    public static function getCommunicationErrorExceptionClass(): string {
        return CodebergCommunicationError::class;
    }
}