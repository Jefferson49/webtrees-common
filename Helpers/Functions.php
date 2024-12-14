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

use Cissee\WebtreesExt\MoreI18N;
use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Webtrees;

use Exception;

/**
 * Functions to be used in webtrees custom modules
 */
class Functions
{

    /**
     * Get interface from container
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
     * Find a specified module, if it is currently active.
     */
    public static function moduleLogInterface(ModuleInterface $module): CustomModuleLogInterface|null
    {
        if (!in_array(CustomModuleLogInterface::class, class_implements($module))) {
            return null;
        }

        return $module;
    }

    /**
     * Add autoload vesta translation for the case that the vesta modules library is not available
     *
     * @param  string $custom_module_directory  The directory of the custom module
     * 
     * @return void
     */
    public static function autoloadVestaTranslation(string $custom_module_directory): void {

        if (self::getFromContainer(MoreI18N::class) === null) {
            $loader = new ClassLoader();
            $loader->addPsr4('Cissee\\WebtreesExt\\', $custom_module_directory . "/vendor/vesta-webtrees-2-custom-modules/vesta_common/patchedWebtrees");
            $loader->register();    
        }
    }
}
