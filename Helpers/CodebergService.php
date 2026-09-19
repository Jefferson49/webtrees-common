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
 * Codeberg services to be used in webtrees custom modules
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
use Jefferson49\Webtrees\Exceptions\CodebergCommunicationError;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

use InvalidArgumentException;
use UnexpectedValueException;

/**
 * A service to connect with Codeberg and request its Forgejo API.
 */
class CodebergService
{
    private const API_URL = 'https://codeberg.org/api/v1';

    /**
     * Get the tag of the latest release of a Codeberg repository
     *
     * @param string $codeberg_repo        The Codeberg repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $codeberg_api_token   A Codeberg API token, to allow a higher frequency of API requests
     * @param string $below_tag            If provided, the latest release below this version will be returned
     *
     * @throws InvalidArgumentException    If $below_tag is not a valid version format
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return string                      The tag of the latest release below the specified version, or an empty string if no such release exists
     */
    public static function getLatestReleaseTag(string $codeberg_repo, string $codeberg_api_token = '', string $below_tag = ''): string
    {
        if ($codeberg_repo === '') {
            return '';
        }

        if ($below_tag !== '') {
            $version_parser = new VersionParser();

            try {
                $below_version = $version_parser->normalize($below_tag);
            } catch (UnexpectedValueException) {
                throw new InvalidArgumentException('Invalid version format for $below_tag: ' . $below_tag);
            }

            for ($page = 1; ; $page++) {
                $response = self::getResponse(self::API_URL . '/repos/' . $codeberg_repo . '/tags?page=' . $page . '&limit=10', $codeberg_api_token);

                if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
                    throw new CodebergCommunicationError('Error occurred while fetching release tags from Codeberg API');
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
                        return $tag['name'];
                    }
                }

                if (count($tags) < 10) {
                    break;
                }
            }

            return '';
        }

        $response = self::getResponse(self::API_URL . '/repos/' . $codeberg_repo . '/releases/latest', $codeberg_api_token);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $release = json_decode($response->getBody()->getContents(), true);

            return is_array($release) && isset($release['tag_name']) && is_string($release['tag_name'])
                ? $release['tag_name']
                : '';
        }

        return '';
    }

    /**
     * Where can we download a release of the Codeberg repository
     *
     * @param string $codeberg_repo        The Codeberg repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $version              The version of the module; latest version if empty
     * @param string $tag_prefix           A prefix for the version tag, e.g. 'v' in case of 'v1.2.3'
     * @param string $codeberg_api_token   A Codeberg API token, to allow a higher frequency of API requests
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return string                      The download URL, or an empty string if none is available
     */
    public static function downloadUrl(string $codeberg_repo, string $version, string $tag_prefix, string $codeberg_api_token = ''): string
    {
        $version = str_replace(["\n", "\r"], ['', ''], $version);

        if ($version !== '' && strlen($version) > strlen($tag_prefix) && substr($version, 0, strlen($tag_prefix)) !== $tag_prefix) {
            $version = $tag_prefix . $version;
        }

        $url = self::API_URL . '/repos/' . $codeberg_repo . '/releases/' . ($version === '' ? 'latest' : 'tags/' . $version);
        $response = self::getResponse($url, $codeberg_api_token);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            return '';
        }

        $release = json_decode($response->getBody()->getContents(), true);

        if (!is_array($release)) {
            return '';
        }

        foreach ($release['assets'] ?? [] as $asset) {
            if (is_array($asset) && isset($asset['browser_download_url']) && is_string($asset['browser_download_url'])) {
                return $asset['browser_download_url'];
            }
        }

        return isset($release['zipball_url']) && is_string($release['zipball_url']) ? $release['zipball_url'] : '';
    }

    /**
     * Get the text of a file from a Codeberg repository
     *
     * @param string $repo                 The Codeberg repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $branch               The Codeberg tag or branch
     * @param string $path                 The path on Codeberg including the file name
     * @param string $codeberg_api_token   A Codeberg API token, to allow a higher frequency of API requests
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return string                      The file contents, or an empty string if the file cannot be read
     */
    public static function getTextFileContent(string $repo, string $branch, string $path, string $codeberg_api_token = ''): string
    {
        if ($repo === '') {
            return '';
        }

        $url = self::API_URL . '/repos/' . $repo . '/contents/' . $path . '?ref=' . rawurlencode($branch);
        $response = self::getResponse($url, $codeberg_api_token);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            return '';
        }

        $file_object = json_decode($response->getBody()->getContents(), true);

        if (is_array($file_object) && isset($file_object['content']) && is_string($file_object['content'])) {
            return base64_decode($file_object['content']) ?: '';
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
     * @param string $codeberg_repo        The Codeberg repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $codeberg_api_token   A Codeberg API token, to allow a higher frequency of API requests
     * @param string $below_tag            If provided, only consider releases below this tag
     * @param int    $release_count        The number of recent releases to consider
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return array{tag: string, published_at: string, max_downloads: int}  The latest tag, publication date and maximum download count (-1 if unavailable)
     */
    public static function getRecentReleasesInfo(string $codeberg_repo, string $codeberg_api_token = '', string $below_tag = '', int $release_count = 3): array
    {
        $result = ['tag' => '', 'published_at' => '', 'max_downloads' => -1];

        if ($codeberg_repo === '') {
            return $result;
        }

        $response = self::getResponse(self::API_URL . '/repos/' . $codeberg_repo . '/releases?limit=' . $release_count, $codeberg_api_token);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            return $result;
        }

        $releases = json_decode($response->getBody()->getContents(), true);

        if (!is_array($releases) || $releases === []) {
            return $result;
        }

        $result['published_at'] = (string) ($releases[0]['published_at'] ?? '');
        $max_downloads = 0;
        $version_parser = new VersionParser();

        try {
            $below_tag = $below_tag === '' ? '' : $version_parser->normalize($below_tag);
        } catch (UnexpectedValueException) {
            $below_tag = '';
        }

        foreach ($releases as $release) {
            if (!is_array($release)) {
                continue;
            }

            $release_downloads = 0;
            foreach ($release['assets'] ?? [] as $asset) {
                if (is_array($asset)) {
                    $release_downloads += (int) ($asset['download_count'] ?? 0);
                }
            }
            $max_downloads = max($max_downloads, $release_downloads);

            if (isset($release['tag_name']) && is_string($release['tag_name'])) {
                try {
                    $release_tag = $version_parser->normalize($release['tag_name']);
                } catch (UnexpectedValueException) {
                    continue;
                }

                if (($below_tag === '' || Comparator::lessThan($release_tag, $below_tag)) && $result['tag'] === '') {
                    $result['tag'] = $release['tag_name'];
                }
            }
        }

        $result['max_downloads'] = $max_downloads;

        return $result;
    }

    /**
     * Get the release notes of the latest release of a Codeberg repository
     *
     * @param string $codeberg_repo        The Codeberg repository, e.g. 'Jefferson49/webtrees-common'
     * @param string $codeberg_api_token   A Codeberg API token, to allow a higher frequency of API requests
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return string                      The release notes, or an empty string if none are available
     */
    public static function getLatestReleaseNotes(string $codeberg_repo, string $codeberg_api_token = ''): string
    {
        if ($codeberg_repo === '') {
            return '';
        }

        $response = self::getResponse(self::API_URL . '/repos/' . $codeberg_repo . '/releases/latest', $codeberg_api_token);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            return '';
        }

        $release = json_decode($response->getBody()->getContents(), true);

        return is_array($release) && isset($release['body']) && is_string($release['body']) ? $release['body'] : '';
    }

    /**
     * Create a request to Codeberg and return the response
     *
     * @param string $url                  The Codeberg API URL
     * @param string $codeberg_api_token   A Codeberg API token, to allow a higher frequency of API requests
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return ResponseInterface           The HTTP response
     */
    public static function getResponse(string $url, string $codeberg_api_token = ''): ResponseInterface
    {
        if (version_compare(Webtrees::VERSION, '2.3', '>=')) {
            try {
                $http_client = Registry::container()->get(ClientInterface::class);
                $request_factory = Registry::container()->get(RequestFactoryInterface::class);
                $request = $request_factory->createRequest('GET', $url);

                if ($codeberg_api_token !== '') {
                    $request = $request->withHeader('Authorization', 'token ' . $codeberg_api_token);
                }

                return $http_client->sendRequest($request);
            } catch (ClientExceptionInterface $ex) {
                throw new CodebergCommunicationError($ex->getMessage());
            }
        }

        try {
            $client = new Client(['timeout' => 3]);
            $options = [];

            if ($codeberg_api_token !== '') {
                $options['headers'] = ['Authorization' => 'token ' . $codeberg_api_token];
            }

            return $client->get($url, $options);
        } catch (GuzzleException $ex) {
            throw new CodebergCommunicationError($ex->getMessage());
        }
    }
}