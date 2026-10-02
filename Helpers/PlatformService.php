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
 * Hosting platform services to be used in webtrees custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Webtrees;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Jefferson49\Webtrees\Exceptions\HostingPlatformCommunicationError;

use InvalidArgumentException;
use ReflectionClass;
use UnexpectedValueException;


/**
 * A service to connect with a platform and request the platform API
 */
abstract class PlatformService
{
    /**
     * Get the name of the hosting platform
     */
    public static function getPlatformName(): string {
        return '';
    }

    /**
     * Get the platform URL of the hosting platform
     */
    public static function getPlatformUrl(): string {
        return '';
    }

    /**
     * Get the API URL of the hosting platform
     */
    public static function getApiUrl(): string {
        return '';
    }

    /**
     * Get the class for an communication error exception
     */
    public static function getCommunicationErrorExceptionClass(): string {
        return '';
    }

    /**
     * Get the tag of the latest release of a repository
     *
     * @param string $repo       The repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $api_token  An API token, to allow a higher frequency of API requests
     * @param string $below_tag  If provided, the latest release below this version will be returned
     *
     * @throws InvalidArgumentException           If $below_tag is not a valid version format
     * @throws HostingPlatformCommunicationError  In case of a communcation error with the hosting platform
     *
     * @return string                             The tag of the latest release below the specified version, or an empty string if no such release exists
     */
    public static function getLatestReleaseTag(string $repo, string $api_token = '', string $below_tag = ''): string
    {
        if ($repo !== '') {

            //If we need to search for a version below a certain version, we need to iterate through the releases and compare the versions
            if ($below_tag !== '') {
                $version_parser = new VersionParser();

                try {
                    $below_version = $version_parser->normalize($below_tag);
                } catch (UnexpectedValueException) {
                    throw new InvalidArgumentException('Invalid version format for $below_tag: ' . $below_tag);
                }

                //We iterate through the releases returned by the platform API.
                //Releases are in reverse chronological order, with the newest release first
                for ($page = 1; ; $page++) {
                    $api_url = static::getApiUrl() . '/repos/' . $repo;

                    if (static::getPlatformName() === GithubService::getPlatformName()) {
                        $api_url . '/tags?per_page=10&page=' . $page;
                    }
                    elseif (static::getPlatformName() === GithubService::getPlatformName()) {
                        $api_url . '/tags?page=' . $page . '&limit=10';
                    }

                    $response = static::getResponse($api_url, $api_token);

                    if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
                        $ref = new ReflectionClass(static::getCommunicationErrorExceptionClass());
                        throw $ref->newInstance('Error occurred while fetching release tags from the ' . static::getPlatformName() . ' API');
                    }

                    $tags = json_decode($response->getBody()->getContents(), true);

                    if (!is_array($tags) || $tags === []) {
                        break;
                    }

                    foreach ($tags as $tag) {
                        if (!is_array($tag) || !isset($tag['name']) || !is_string($tag['name'])) {
                            continue;
                        }

                        try {
                            $tag_version = $version_parser->normalize($tag['name']);
                        } catch (UnexpectedValueException) {
                            continue;
                        }

                        if (Comparator::lessThan($tag_version, $below_version)) {

                            //Since the tags are in reverse chronological order, we can stop searching once we find the first tag that is less than the below_version
                            return $tag['name'];
                        }
                    }

                    if (count($tags) < 10) {
                        break;
                    }
                }

                //If we did not find anything below the specified version, return empty string
                return '';
            }

            //If we do not need to search for a version below a certain version, we retrieve the latest release
            $api_url = static::getApiUrl() . '/repos/'. $repo . '/releases/latest';

            $response = static::getResponse($api_url, $api_token);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $content = $response->getBody()->getContents();

                if (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                    return $matches[1];
                }
            }
        }

        return '';
    }

    /**
     * Where can we download a release from the repository
     *
     * @param string $repo        The repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $version     The version of the module; latest version if empty
     * @param string $tag_prefix  A prefix for the verison tag, e.g. 'v' in case of 'v1.2.3'
     * @param string $api_token   An API token, to allow a higher frequency of API requests
     *
     * @throws HostingPlatformCommunicationError  In case of communcation error with the platform
     *
     * @return string
     */
    public static function downloadUrl(string $repo, string $version, string $tag_prefix, string $api_token = ''): string
    {
        //Remove wrong line feed characters, e.g. at the end of a version
		$version = str_replace(["\n", "\r"], ['', ''], $version);

        //Add prefix, if the tags of the repository have a prefix
        if ($version !== '' && strlen($version) > strlen($tag_prefix)) {

            //If version does not start with prefix, add prefix
            if (substr($version, 0, strlen($tag_prefix)) !== $tag_prefix) {
                $version = $tag_prefix . $version;
            }
        }

        $download_url   = '';
        $api_url = static::getApiUrl() . '/repos/'. $repo . '/releases/';

        // If no tag is provided get the download URL of the latest release
        if ($version === '') {
            $url = $api_url . 'latest';
        }
        // Get the download URL for a certain tag
        else {
            $url = $api_url . 'tags/' . $version;
        }

        // Get the download URL from the platform
        $response = static::getResponse($url, $api_token);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $content = $response->getBody()->getContents();

            if (preg_match('/"browser_download_url":"([^"]+?)"/', $content, $matches) === 1) {
                $download_url = $matches[1];
            }
            elseif (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                $download_url = static::getPlatformUrl() . $repo . '/archive/refs/tags/' . $matches[1] . '.zip';
            }
        }

        return $download_url;
    }

    /**
     * Get the text of a file from the repository
     *
     * @param string $repo       The repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $branch     The tag or branch
     * @param string $path       The path in the repository including the file name
     * @param string $api_token  An API token, to allow a higher frequency of API requests
     *
     * @throws HostingPlatformCommunicationError  In case of a communcation error with the platform
     *
     * @return string
     */
    public static function getTextFileContent(string $repo, string $branch, string $path, string $api_token = ''): string
    {
        if ($repo !== '') {

            $api_url = static::getApiUrl() . '/repos/'. $repo .'/contents/' . $path . '?ref=' . $branch;

            $response = static::getResponse($api_url, $api_token);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {

                $content = $response->getBody()->getContents();
                $file_object = json_decode($content, true);

                if (isset($file_object['content'])) {
                    $file_content = base64_decode($file_object['content']);
                    return $file_content;
                }
            }
        }

        return '';
    }

    /**
     * Get combined release information: latest version tag and maximum download count
     * across the most recent releases.
     *
     * This method fetches multiple releases in a single API call, extracting both
     * the latest version tag and the maximum download count (as a proxy for popularity).
     *
     * @param string $repo           The repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $api_token      An API token, to allow a higher frequency of API requests
     * @param string $below_tag      If provided, only consider releases below this tag
     * @param int    $release_count  The number of recent releases to consider
     *
     * @throws HostingPlatformCommunicationError  In case of a communication error with the platform
     *
     * @return array{tag: string, max_downloads: int}  The latest tag and max download count (-1 if unavailable)
     */
    public static function getRecentReleasesInfo(string $repo, string $api_token = '', string $below_tag = '', int $release_count = 3): array
    {
        //Default result
        $result = [
            'tag' => '',
            'published_at' => '',
            'max_downloads' => -1,
        ];

        if ($repo === '') {
            return $result;
        }

        $api_url = static::getApiUrl() . '/repos/' . $repo . '/releases?per_page=' . $release_count;

        if (static::getPlatformName() === GithubService::getPlatformName()) {
            $api_url . '/releases?per_page=' . $release_count;
        }
        elseif (static::getPlatformName() === GithubService::getPlatformName()) {
            $api_url . '/releases?limit=' . $release_count;
        }

        $response = static::getResponse($api_url, $api_token);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $releases = json_decode($response->getBody()->getContents(), true);

            if (!is_array($releases) || $releases === []) {
                return $result;
            }

            if (isset($releases[0]['published_at'])) {
                $result['published_at'] = $releases[0]['published_at'];
            }

            // Max download count across all fetched releases
            $max_downloads = 0;
            $version_parser = new VersionParser();

            if ($below_tag !== '') {
                try {
                    $below_tag = $version_parser->normalize($below_tag);
                } catch (UnexpectedValueException) {
                    // If below tag is no valid version format, ignore the filter
                    $below_tag = '';
                }
            }

            //We iterate through the releases returned by the platform API.
            //Releases are in reverse chronological order, with the newest release first
            foreach ($releases as $release) {
                $release_downloads = 0;

                if (isset($release['assets']) && is_array($release['assets'])) {
                    foreach ($release['assets'] as $asset) {
                        $release_downloads += (int) ($asset['download_count'] ?? 0);
                    }
                }

                if ($release_downloads > $max_downloads) {
                    $max_downloads = $release_downloads;
                }

                // Get latest version; also consider the $below_tag parameter to filter releases
                if (isset($release['tag_name']) && is_string($release['tag_name'])) {
                    try {
                        $release_tag = $version_parser->normalize($release['tag_name']);
                    } catch (UnexpectedValueException) {
                        continue;
                    }

                    if ($below_tag !== '' && !Comparator::lessThan($release_tag, $below_tag)) {
                        // Skip releases that are not below the specified tag
                        continue;
                    }

                    //Since the tags are in reverse chronological order, we can stop searching once we find the first tag that is less than the below_version
                    if ($result['tag'] === '') {
                        $result['tag'] = $release['tag_name'];
                    }
                }
            }

            $result['max_downloads'] = $max_downloads;
        }

        return $result;
    }

    /**
     * Get the release notes of the latest release of a repository
     *
     * @param string $repo        The repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $api_token   An API token, to allow a higher frequency of API requests
     *
     * @throws HostingPlatformCommunicationError  In case of a communcation error with the platform
     *
     * @return string
     */
    public static function getLatestReleaseNotes(string $repo, string $api_token = ''): string
    {
        if ($repo !== '') {

            $api_url = static::getApiUrl() . '/repos/'. $repo . '/releases/latest';
            $response = static::getResponse($api_url, $api_token);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {

                $content = (array) Json_decode($response->getBody()->getContents());
                $body = $content['body'] ?? '';
                return $body;
            }
        }

        return '';
    }

    /**
     * Create a request to the hosting platform and return the response
     *
     * @param string $url         The repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $api_token   An API token, to allow a higher frequency of API requests
     *
     * @throws HostingPlatformCommunicationError  In case of a communcation error with the platform
     *
     * @return string
     */
    public static function getResponse(string $url, string $api_token = ''): ResponseInterface
    {
        if (version_compare(Webtrees::VERSION, '2.3', '>=')) {
            try {
                $http_client     = Registry::container()->get(ClientInterface::class);
                $request_factory = Registry::container()->get(RequestFactoryInterface::class);
                $request         = $request_factory->createRequest('GET', $url);

                if ($api_token !== '') {
                    $request = $request->withHeader('Authorization', 'Bearer ' . $api_token);
                }

                return $http_client->sendRequest($request);

            } catch (ClientExceptionInterface $ex) {
                // Can't connect to the server?
                $ref = new ReflectionClass(static::getCommunicationErrorExceptionClass());
                throw $ref->newInstance($ex->getMessage());
            }
        }
        else {
            try {
                $client = new Client(
                    [
                    'timeout' => 3,
                    ]
                );

                $options = [];

                if ($api_token !== '') {
                    $options['headers'] = ['Authorization' => 'Bearer ' . $api_token];
                }

                return $client->get($url, $options);

            } catch (GuzzleException $ex) {
                // Can't connect to the hosting platform?
                $ref = new ReflectionClass(static::getCommunicationErrorExceptionClass());
                throw $ref->newInstance($ex->getMessage());
            }
        }
    }
}
