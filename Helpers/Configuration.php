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
 * webtrees configuration services to be used in custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Fisharebest\Webtrees\Webtrees;

use function file_exists;
use function parse_ini_file;


/**
 * Functions to be used for handling the webtrees configuration within custom modules
 */
class Configuration
{
    //All configured options from the webtrees config.ini.php file
    private static $webtrees_config = [];

	/**
     * Get all options from the webtrees config.ini.php file
     * 
     * @return array An array with the options. Empty if options could not be read.
     */ 

    public static function getWebtreesConfig(): array {

        // If not already available, read the configuration settings from the webtrees config file
        if (self::$webtrees_config === [] && file_exists(Webtrees::CONFIG_FILE)) {
            self::$webtrees_config  = parse_ini_file(Webtrees::CONFIG_FILE);
        }

        return self::$webtrees_config;
    }

	/**
     * Get the value for a certain key in the webtrees configuration (from config.ini.php file)
     * 
     * @param string $key
     * 
     * @return string
     */ 

    public static function getConfigValue(string $key): string {

        if (isset(self::getWebtreesConfig()[$key])) {
            return self::getWebtreesConfig()[$key];
        } else {
            return '';
        }
    }    
}
