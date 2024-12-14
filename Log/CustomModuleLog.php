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
 * Custom module specific logs
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Log;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\DB;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Custom module specific logs
 */
class CustomModuleLog extends Log
{
    //Define additonal debug type
    private const string TYPE_DEBUG = 'debug';

    /**
     * Only if debugging is activated, store a new module specific debug log in the message log.
     *
     * @param CustomModuleLogInterface $custom_module
     * @param string                   $message
     * @param Tree|null                $tree
     *
     * @return void
     */
    public static function addDebugLog(CustomModuleLogInterface $custom_module, string $message, Tree|null $tree = null): void
    {
        if ($custom_module->debuggingActivated()) {
            self::addModuleLog($custom_module, $message, self::TYPE_DEBUG, $tree);
        }
    }

    /**
     * Store a new module specific message (of the appropriate type) in the message log.
     * code from: Fisharebest\Webtree\Log::addLog;
     *
     * @param string    $message
     * @param string    $log_type
     * @param Tree|null $tree
     *
     * @return void
     */
    private static function addModuleLog(CustomModuleLogInterface $custom_module, string $message, string $log_type, Tree|null $tree = null): void
    {
        //Add custom module specific prefix to log message
        $prefix = $custom_module->getLogPrefix();
        $message = $prefix . ': ' . $message;

        if (Registry::container()->has(ServerRequestInterface::class)) {
            $request    = Registry::container()->get(ServerRequestInterface::class);
            $ip_address = Validator::attributes($request)->string('client-ip');
        } else {
            $ip_address = '127.0.0.1';
        }

        DB::table('log')->insert([
            'log_type'    => $log_type,
            'log_message' => $message,
            'ip_address'  => $ip_address,
            'user_id'     => Auth::id(),
            'gedcom_id'   => $tree?->id(),
        ]);
    }    
}
