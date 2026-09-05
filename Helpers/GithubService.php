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
use Jefferson49\Webtrees\Exceptions\GithubCommunicationError;

use InvalidArgumentException;
use UnexpectedValueException;


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
     * @param string $below_tag          If provided, the latest release below this version will be returned
     *
     * @throws GithubCommunicationError  In case of a communcation error with GitHub
     *
     * @return string
     */
    public static function getLatestReleaseTag(string $github_repo, string $github_api_token = '', string $below_tag = ''): string
    {
        if ($github_repo !== '') {

            if ($below_tag !== '') {
                $version_parser = new VersionParser();

                try {
                    $below_version = $version_parser->normalize($below_tag);
                } catch (UnexpectedValueException) {
                    throw new InvalidArgumentException('Invalid version format for $below_tag: ' . $below_tag);
                }

                $latest_tag = '';
                $latest_version = '';

                for ($page = 1; ; $page++) {
                    $github_api_url = 'https://api.github.com/repos/' . $github_repo . '/tags?per_page=100&page=' . $page;
                    $response = self::getResponse($github_api_url, $github_api_token);

                    if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
                        throw new GithubCommunicationError('Error occurred while fetching release tags from GitHub API');
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

                        if (Comparator::lessThan($tag_version, $below_version)
                            && ($latest_version === '' || Comparator::greaterThan($tag_version, $latest_version))) {
                            $latest_tag = $tag['name'];
                            $latest_version = $tag_version;
                        }
                    }

                    if (count($tags) < 100) {
                        break;
                    }
                }

                return $latest_tag;
            }

            $github_api_url = 'https://api.github.com/repos/'. $github_repo . '/releases/latest';

            $response = self::getResponse($github_api_url, $github_api_token);

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
    public static function downloadUrl(string $github_repo, string $version, string $tag_prefix, string $github_api_token = ''): string
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
        $response = self::getResponse($url, $github_api_token);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $content = $response->getBody()->getContents();

            if (preg_match('/"browser_download_url":"([^"]+?)"/', $content, $matches) === 1) {
                $download_url = $matches[1];
            }
            elseif (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                $download_url = 'https://github.com/' . $github_repo . '/archive/refs/tags/' . $matches[1] . '.zip';
            }
        }

        return $download_url;
    }

    /**
     * Get the text of a file from a GitHub repository
     *
     * @param string $repo              The GitHub repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $branch            The GitHub tag or branch
     * @param string $path              The path on GitHub including the file name
     * @param string $github_api_token  A GitHub API token, to allow a higher frequency of API requests
     *
     * @throws GithubCommunicationError  In case of a communcation error with GitHub
     *
     * @return string
     */
    public static function getTextFileContent(string $repo, string $branch, string $path, string $github_api_token = ''): string
    {
        if ($repo !== '') {

            $github_api_url = 'https://api.github.com/repos/'. $repo .'/contents/' . $path . '?ref=' . $branch;

            $response = self::getResponse($github_api_url, $github_api_token);

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
     * @param string $github_repo        The GitHub repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $github_api_token   A GitHub API token, to allow a higher frequency of API requests
     * @param string $below_tag          If provided, only consider releases below this tag
     * @param int    $release_count      The number of recent releases to consider
     *
     * @throws GithubCommunicationError  In case of a communication error with GitHub
     *
     * @return array{tag: string, max_downloads: int}  The latest tag and max download count (-1 if unavailable)
     */
    public static function getRecentReleasesInfo(string $github_repo, string $github_api_token = '', string $below_tag = '', int $release_count = 3): array
    {
        $github_api_url = 'https://api.github.com/repos/' . $github_repo . '/releases?per_page=' . $release_count;

        //Default result
        $result = [
            'tag' => '',
            'published_at' => '',
            'max_downloads' => -1,
        ];

        if ($github_repo === '') {
            return $result;
        }

        $response = self::getResponse($github_api_url, $github_api_token);

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
                    // Update the latest tag if the current release is newer
                    if (!isset($result['tag']) || Comparator::greaterThan($release_tag  , $result['tag'])) {
                        $result['tag'] = $release['tag_name'];
                    }
                }
            }

            $result['max_downloads'] = $max_downloads;
        }

        return $result;
    }

    /**
     * Get the release notes of the latest release of a GitHub repository
     *
     * @param string $github_repo        The GitHub repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $github_api_token   A GitHub API token, to allow a higher frequency of API requests
     *
     * @throws GithubCommunicationError  In case of a communcation error with GitHub
     *
     * @return string
     */
    public static function getLatestReleaseNotes(string $github_repo, string $github_api_token = ''): string
    {
        if ($github_repo !== '') {

            $github_api_url = 'https://api.github.com/repos/'. $github_repo . '/releases/latest';
            $response = self::getResponse($github_api_url, $github_api_token);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {

                $content = (array) Json_decode($response->getBody()->getContents());
                $body = $content['body'] ?? '';
                return $body;
            }
        }

        return '';
    }

    /**
     * Create a request to GitHub and return the response
     *
     * @param string $url                The GitHub repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $github_api_token   A GitHub API token, to allow a higher frequency of API requests
     *
     * @throws GithubCommunicationError  In case of a communcation error with GitHub
     *
     * @return string
     */
    public static function getResponse(string $url, string $github_api_token = ''): ResponseInterface
    {
        if (version_compare(Webtrees::VERSION, '2.3', '>=')) {
            try {
                $http_client     = Registry::container()->get(ClientInterface::class);
                $request_factory = Registry::container()->get(RequestFactoryInterface::class);
                $request         = $request_factory->createRequest('GET', $url);

                if ($github_api_token !== '') {
                    $request = $request->withHeader('Authorization', 'Bearer ' . $github_api_token);
                }

                return $http_client->sendRequest($request);

            } catch (ClientExceptionInterface $ex) {
                // Can't connect to the server?
                throw new GithubCommunicationError($ex->getMessage());
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

                if ($github_api_token !== '') {
                    $options['headers'] = ['Authorization' => 'Bearer ' . $github_api_token];
                }

                return $client->get($url, $options);

            } catch (GuzzleException $ex) {
                // Can't connect to GitHub?
                throw new GithubCommunicationError($ex->getMessage());
            }
        }
    }
}
