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

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Webtrees;
use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\JoinClause;
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

    /**
     * All the trees, even if current user has no permission to access
     * This is a modifyed version of the all method of TreeService (which only returns trees with permission)
     *
     * @return Collection<array-key,Tree>
     */
    public static function getAllTrees(): Collection
    {
        return Registry::cache()->array()->remember('all-trees', static function (): Collection {
            // All trees
            $query = DB::table('gedcom')
                ->leftJoin('gedcom_setting', static function (JoinClause $join): void {
                    $join->on('gedcom_setting.gedcom_id', '=', 'gedcom.gedcom_id')
                        ->where('gedcom_setting.setting_name', '=', 'title');
                })
                ->where('gedcom.gedcom_id', '>', 0)
                ->select([
                    'gedcom.gedcom_id AS tree_id',
                    'gedcom.gedcom_name AS tree_name',
                    'gedcom_setting.setting_value AS tree_title',
                ])
                ->orderBy('gedcom.sort_order')
                ->orderBy('gedcom_setting.setting_value');

            return $query
                ->get()
                ->mapWithKeys(static function (object $row): array {
                    return [$row->tree_name => Tree::rowMapper()($row)];
                });
        });
    }

    /**
     * Check if tree is a valid tree
     *
     * @return bool
     */ 
    public static function isValidTree(string $tree_name): bool
    {
       $find_tree = self::getAllTrees()->first(static function (Tree $tree) use ($tree_name): bool {
           return $tree->name() === $tree_name;
       });
       
       $is_valid_tree = $find_tree instanceof Tree;
       
       return $is_valid_tree;
    }

	/**
     * Get an array [name => title] for all trees, for which the current user is manager
     * 
     * @param Collection $trees The trees, for which the list shall be generated
     *
     * @return array            error message
     */ 
    public static function getTreeNameTitleList(Collection $trees): array {

        $tree_list = [];

        foreach($trees as $tree) {
            if (Auth::isManager($tree)) {
                $tree_list[$tree->name()] = $tree->name() . ' (' . $tree->title() . ')';
            }
        }   

        return $tree_list;
    }

    /**
     * Get a module setting for a module. Return a default if the setting is not set.
     *
     * @param string $module_name
     * @param string $setting_name
     * @param string $default
     *
     * @return string
     */
    final public static function getPreferenceForModule(string $module_name, string $setting_name, string $default = ''): string
    {
        //Code from: webtrees AbstractModule->getPreference
        return DB::table('module_setting')
            ->where('module_name', '=', $module_name)
            ->where('setting_name', '=', $setting_name)
            ->value('setting_value') ?? $default;
    }
}
