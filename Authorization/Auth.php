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
 * Extended Authorization class with additional ENUM values, which were removed in webtrees 2.3
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Authorization;

use Fisharebest\Webtrees\Auth as WebtreesAuth;


/**
 * Extended Auth class with additional ENUM values, which were removed in webtrees 2.3
 */
class Auth extends WebtreesAuth
{
    // Privacy constants
    public const int PRIV_PRIVATE = 2; // Allows visitors to view the item
    public const int PRIV_USER    = 1; // Allows members to access the item
    public const int PRIV_NONE    = 0; // Allows managers to access the item
    public const int PRIV_HIDE    = -1; // Hide the item to all users
}