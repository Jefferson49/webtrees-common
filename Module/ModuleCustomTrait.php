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
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait as WebtreesModuleCustomTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Jefferson49\Webtrees\Exceptions\GithubCommunicationError;
use Jefferson49\Webtrees\Helpers\GithubService;
use Jefferson49\Webtrees\Internationalization\MoreI18N;

use LogicException;


/**
 * Trait ModuleCustomTrait - certain default implementations of ModuleCustomInterface
 * 
 * Consuming classes must define the following constants:
 * CUSTOM_AUTHOR, CUSTOM_VERSION, GITHUB_REPO
 *
 */
trait ModuleCustomTrait 
{
    use WebtreesModuleCustomTrait;

    //A list of custom views, which are registered by the module
    private Collection $custom_view_list;


    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::moduleCustomConstant('CUSTOM_AUTHOR');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::moduleCustomConstant('CUSTOM_VERSION');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleLatestVersion()
     */
    public function customModuleLatestVersion(): string
    {
        return Registry::cache()->file()->remember(
            $this->name() . '-latest-version',
            function (): string {

                try {
                    //Get latest release from GitHub
                    return GithubService::getLatestReleaseTag(self::moduleCustomConstant('GITHUB_REPO'));
                }
                catch (GithubCommunicationError $ex) {
                    // Can't connect to GitHub?
                    return $this->customModuleVersion();
                }
            },
            86400
        );
    } 

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/' . self::moduleCustomConstant('GITHUB_REPO');
    }

    /**
     * {@inheritDoc}
     *
     * @param string $language
     *
     * @return array
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customTranslations()
     */
    public function customTranslations(string $language): array
    {
        return MoreI18N::readTranslationsFromMoFile($this->resourcesFolder() . 'lang/', $language);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     */
    public function resourcesFolder(): string
    {
        return $this->moduleFolder() . '/resources/';
    }

    /**
     * Get the module folder
     *
     * @return string
     */
    public function moduleFolder(): string
    {
        $folder = $this->name();
        $folder = substr($this->name(), 1);
        $folder = substr($folder, 0, strlen($folder) - 1);
        return Webtrees::MODULES_DIR . $folder;
    }

    /**
     * Get the namespace for the views
     *
     * @return string
     */
    public static function viewsNamespace(): string
    {
        return self::class;
    }

    /**
     * Retrieve a required class constant from the concrete module class.
     */
    private static function moduleCustomConstant(string $name): string
    {
        $class = static::class;
        $constant = $class . '::' . $name;

        if (!defined($constant)) {
            throw new LogicException(sprintf('Missing required constant %s in class %s', $name, $class));
        }

        return constant($constant);
    }

    /**
     * Check if module version is new and start update activities if needed
     *
     * @return void
     */
    public function checkModuleVersionUpdate(): void
    {
        //If new custom module version is detected
        if ($this->getPreference(self::PREF_MODULE_VERSION) !== self::moduleCustomConstant('CUSTOM_VERSION')) {

            //Update prefences stored in database
            $update_result = $this->updatePreferences();

            //Show flash message for error or sucessful update of preferences
            if ($update_result !== '') {

                $message = I18N::translate('Error while trying to update the custom module "%s" to the new module version %s: ' . $update_result, $this->title(), self::moduleCustomConstant('CUSTOM_VERSION'));
                FlashMessages::addMessage($message, 'danger');
            } 
            else {
                $message = I18N::translate('The preferences for the custom module "%s" were sucessfully updated to the new module version %s.', $this->title(), self::moduleCustomConstant('CUSTOM_VERSION'));
                FlashMessages::addMessage($message, 'success');	    
                
                //Update custom module version
                $this->setPreference(self::PREF_MODULE_VERSION, self::moduleCustomConstant('CUSTOM_VERSION'));
            }
        }        
    }

    /**
     * Update the preferences (after new module version is detected)
     *
     * @return string Error message if an error occurred, otherwise empty string
     */
    public function updatePreferences(): string
    {   
        return '';
    }    

    /**
     * Check availability of the registered custom views and show flash messages with warnings if any errors occur 
     *
     * @return void
     */
    private function checkCustomViewAvailability() : void {

        $custom_modules = new ModuleService()
            ->findByInterface(ModuleCustomInterface::class)
            ->filter(function ($module) {
                return $module->name() !== $this->name();
            }
        );

        foreach($this->custom_view_list as $custom_view) {

            [$namespace, $view_name] = explode(View::NAMESPACE_SEPARATOR, (string) $custom_view, 2);

            $view = new View('test');

            try {
                $file_name = $view->getFilenameForView($view_name);

                //Check if the view is registered with a file path other than the current module; e.g. another moduleS probably registered it with an unknown views namespace
                if (mb_strpos($file_name, $this->resourcesFolder()) === false) {
                    throw new LogicException;
                }
            }
            catch (LogicException $e) {

                $message =  '<b>' . I18N::translate('Error') . ':</b><br>' .
                            I18N::translate(
                                'Error in custom view registration. The custom view "%s" has already been registered by another module. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the module with the parallel view is: %s',
                                '<b>' . View::NAMESPACE_SEPARATOR . $view_name . '</b>', '<b>' . $file_name  . '</b>');
                FlashMessages::addMessage($message, 'danger');
            }
        }
        
        return;
    }   
}
