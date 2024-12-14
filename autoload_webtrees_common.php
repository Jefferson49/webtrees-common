<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2024 webtrees development team
 *                    <http://webtrees.net>
 *
 * Jefferson49/webtrees-common: Library to share common code between webtrees custom modules
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
 * Autoload the jefferson49/webtrees-common library to share 
 * common code between webtrees custom modules
 * 
 */

$vendor_directory = __DIR__ . '/../..';

$loader = new Composer\Autoload\ClassLoader($vendor_directory);

try {
    $autoload_common_library_version = Composer\InstalledVersions::getVersion('jefferson49/webtrees-common');
}
catch (\OutOfBoundsException $e) {
    $autoload_common_library_version = '';
}

$local_composer_versions = require $vendor_directory . '/composer/installed.php';
$local_common_library_version = $local_composer_versions['versions']['jefferson49/webtrees-common']['version'];

//If the found library is later than the current autoload version, prepend the found library to autoload
//This ensures that always the latest library version is autoloaded
if (version_compare($local_common_library_version, $autoload_common_library_version, '>')) {
    $loader->addPsr4('Jefferson49\\Webtrees\\Common\\Helpers\\', $vendor_directory . '/jefferson49/webtrees-common/Helpers');
    $loader->addPsr4('Jefferson49\\Webtrees\\Common\\Internationalization\\', $vendor_directory. '/jefferson49/webtrees-common/Internationalization');    
    $loader->addPsr4('Jefferson49\\Webtrees\\Common\\Log\\', $vendor_directory . '/jefferson49/webtrees-common/Log');
    $loader->register(true);    
}
