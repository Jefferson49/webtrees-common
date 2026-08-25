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
 * Services for class names (depending on the webtrees version)
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Fisharebest\Webtrees\Webtrees;


/**
 * Services for class names (depending on the webtrees version)
 */
class ClassName
{
    public const DATA_FIX_PAGE = 'DataFixPage';
    public const CONTROL_PANEL = 'ControlPanel';
    public const HOME_PAGE     = 'HomePage';


    private const CLASS_NAMES = [
        self::CONTROL_PANEL => [
            '2.1' =>  \Fisharebest\Webtrees\Http\RequestHandlers\ControlPanel::class,
            '2.3' =>  \Fisharebest\Webtrees\Http\Controllers\ControlPanel::class,
        ],
        self::DATA_FIX_PAGE => [
            '2.1' =>  \Fisharebest\Webtrees\Http\RequestHandlers\DataFixPage::class,
            '2.3' =>  \Fisharebest\Webtrees\Http\Controllers\DataFixPage::class,
        ],
        self::HOME_PAGE => [
            '2.1' =>  \Fisharebest\Webtrees\Http\RequestHandlers\HomePage::class,
            '2.3' =>  \Fisharebest\Webtrees\Http\Controllers\HomePage::class,
        ],
    ];

    /**
     * Get the class name (depending on the webtrees version)
     *
     * @param  string $name
     *
     * @return string
     */
    public static function get(string $name) : string {

        if (version_compare(Webtrees::VERSION, '2.3', '>=')) {
            return self::CLASS_NAMES[$name]['2.3'] ?? '';
        } else {
            return self::CLASS_NAMES[$name]['2.1'] ?? '';
        }
    }
}