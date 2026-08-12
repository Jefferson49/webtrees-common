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
 * Extended Auth class with additional functions for compatibility with different webtrees versions
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Authorization;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\Webtrees;
use Fisharebest\Webtrees\Tree;


/**
 * Extended Auth class with additional functions for compatibility with different webtrees versions
 */
class AuthFunctions extends Auth
{
    /**
     * What is the user's access level within a tree?
     * 
     * @param Tree           $tree
     * @param ?UserInterface $user
     * 
     * @return int
     */
    public static function accessLevelForTree(Tree $tree, ?UserInterface $user = null): int
    {
        if (version_compare(Webtrees::VERSION, '2.2.6', '>')) {

            $access_level = parent::accessLevel($tree, $user);
            return $access_level->value;
            
        } else {
            return parent::accessLevel($tree, $user);
        }
    }
}
