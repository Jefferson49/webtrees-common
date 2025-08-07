<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
 *
 * Copyright (C) 2025 Markus Hemprich
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
 * GitHub services to be used in webtrees custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Jefferson49\Webtrees\Exceptions\GithubCommunicationError;


/**
 * A service to connect with GitHub and request the GitHub API
 */
class GithubService
{
    /**
     * Get the tag of the latest release of a GitHub repository
     *
     * @param string $github_repo        The GitHub repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $github_api_token   A GitHub API token, to allow a higher frequency of API requests
     *
     * @throws GithubCommunicationError  In case of a communcation error with GitHub
     *  
     * @return string
     */
    public static function getLatestReleaseTag(string $github_repo, string $github_api_token = ''): string
    {
        if ($github_repo !== '') {

            $github_api_url = 'https://api.github.com/repos/'. $github_repo . '/releases/latest';

            try {
                $client = new Client(
                    [
                    'timeout' => 3,
                    ]
                );

                $options = [];

                if ($github_api_token !== '') {
                    $options['headers'] = ['Authorization' => 'Bearer ' . $github_api_token];
                }

                $response = $client->get($github_api_url, $options);

                if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                    $content = $response->getBody()->getContents();
                    
                    if (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                        return $matches[1];
                    }
                }
            } catch (GuzzleException $ex) {
                // Can't connect to GitHub?
                throw new GithubCommunicationError($ex->getMessage());
            }
        }

        return '';
    }

    /**
     * Where can we download a release of the GitHub repository
     * 
     * @param string $github_repo        The GitHub repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $version            The version of the module; latest version if empty
     * @param string $tag_prefix         A prefix for the verison tag, e.g. 'v' in case of 'v1.2.3'
     * @param string $github_api_token   A GitHub API token, to allow a higher frequency of API requests
     * 
     * @throws GithubCommunicationError  In case of communcation error with GitHub
     * 
     * @return string
     */
    public static function downloadUrl(string $github_repo, string $version = '', string $tag_prefix, string $github_api_token = ''): string
    {
        //Remove wrong line feed at the end of a version
        if (strpos($version, "\n", -1)) {
            $version = substr($version, 0, strlen($version) - 1);
        }

        //Add prefix, if the tags of the repository have a prefix
        if ($version !== '' && strlen($version) > strlen($tag_prefix)) {

            //If version does not start with prefix, add prefix
            if (substr($version, 0, strlen($tag_prefix)) !== $tag_prefix) {
                $version = $tag_prefix . $version;
            } 
        }

        $download_url   = '';
        $github_api_url = 'https://api.github.com/repos/'. $github_repo . '/releases/';

        // If no tag is provided get the download URL of the latest release
        if ($version === '') {
            $url = $github_api_url . 'latest';
        }
        // Get the download URL for a certain tag
        else {
            $url = $github_api_url . 'tags/' . $version;
        }

        // Get the download URL from GitHub
        try {
            $client = new Client(
                [
                'timeout'       => 3,
                ]
            );

            $options = [];

            if ($github_api_token !== '') {
                $options['headers'] = ['Authorization' => 'Bearer ' . $github_api_token];
            }

            $response = $client->get($url, $options);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $content = $response->getBody()->getContents();
                
                if (preg_match('/"browser_download_url":"([^"]+?)"/', $content, $matches) === 1) {
                    $download_url = $matches[1];
                }
                elseif (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                    $download_url = 'https://github.com/' . $github_repo . '/archive/refs/tags/' . $matches[1] . '.zip';
                }
            }
        } catch (GuzzleException $ex) {
            // Can't connect to GitHub?
            throw new GithubCommunicationError($ex->getMessage());
        }

        return $download_url;
    }
}
