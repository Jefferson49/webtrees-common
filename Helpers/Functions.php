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
 * Functions to be used in webtrees custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Webtrees;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;

use Exception;

/**
 * Functions to be used in webtrees custom modules
 */
class Functions
{

    /**
     * Get interface from container
     *
     * @param string $id
     * 
     * @return mixed
     */
    public static function getFromContainer(string $id) {

        try {

            if (version_compare(Webtrees::VERSION, '2.2.0', '>=')) {
                return Registry::container()->get($id);
            }
            else {
                return app($id);
            }        
        }
        //Return null if interface was not found
        catch (Exception $e) {
            return null;
        }
    }    

    /**
     * Check if container has a certain interface 
     *
     * @param string $id
     * 
     * @return bool
     */
    public static function containerHas(string $id): bool {

        return self::getFromContainer($id) !== null; 
    }    

    /**
     * Find a specified module, if it is currently active.
     */
    public static function moduleLogInterface(ModuleInterface $module): CustomModuleLogInterface|null
    {
        if (!in_array(CustomModuleLogInterface::class, class_implements($module))) {
            return null;
        }

        return $module;
    }
}
