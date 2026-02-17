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
 * Authorizaton services to be used in webtrees custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Helpers;

use InvalidArgumentException;
use RuntimeException;


/**
 * Functions to be used for authorization within webtrees custom modules
 */
class Authorization
{
    /**
     * Generate a secure random authorization key.
     *
     * @param int $length                Length of the key in characters (must be even).
     * 
     * @return string                    Secure hexadecimal authorization key.
     * @throws InvalidArgumentException  If length is not even. 
     */
    public static function generateAuthKey(int $length = 32) {

        if ($length % 2 !== 0) {
            throw new InvalidArgumentException('Length must be an even number.');
        }
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a secure random password using OpenSSL
     *
     * @param int    $length  Length of the password
     * @param string $chars   Allowed characters
     * 
     * @return string Generated password
     * 
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function generateSecurePassword($length = 12, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_=+[]{};:,.<>?') {

        if ($length <= 0) {
            throw new InvalidArgumentException("Password length must be greater than zero.");
        }

        $charLen = strlen($chars);
        if ($charLen < 2) {
            throw new InvalidArgumentException("Character set must contain at least two characters.");
        }

        // Check if OpenSSL extension is available 
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('The PHP extension openssl is not available.');
        }

        // Generate cryptographically secure random bytes
        $bytes = openssl_random_pseudo_bytes($length);
        if ($bytes === '') {
            throw new RuntimeException("Unable to generate secure random bytes.");
        }

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            // Convert each byte to an index in the allowed characters
            $password .= $chars[ord($bytes[$i]) % $charLen];
        }

        return $password;
    }
}