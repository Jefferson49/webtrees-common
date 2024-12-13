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
 * Autoload the library for common code between webtrees custom modules
 * 
 */


use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use OutOfBoundsException;

$loader = new ClassLoader( __DIR__ .'/vendor');
try {
    $autoload_common_library_version = InstalledVersions::getVersion('jefferson49/webtrees-common');
}
catch (OutOfBoundsException $e) {
    $autoload_common_library_version = '';
}

$local_composer_versions = require __DIR__ . '/vendor/composer/installed.php';
$local_common_library_version = $local_composer_versions['versions']['jefferson49/webtrees-common']['version'];

if (version_compare($local_common_library_version, $autoload_common_library_version, '>=')) {
    $loader->addPsr4('Jefferson49\\Webtrees\\Common\\Helpers\\', __DIR__ . '/vendor/jefferson49/webtrees-common/Helpers');
    $loader->addPsr4('Jefferson49\\Webtrees\\Common\\Internationalization\\', __DIR__ . '/vendor/jefferson49/webtrees-common/Internationalization');    
    $loader->addPsr4('Jefferson49\\Webtrees\\Common\\Log\\', __DIR__ . '/vendor/jefferson49/webtrees-common/Log');
    $loader->register(true);    
}