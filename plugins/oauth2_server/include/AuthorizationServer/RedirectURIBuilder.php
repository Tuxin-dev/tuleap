<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\AuthorizationServer;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * @psalm-immutable
 */
class RedirectURIBuilder
{
    public static function buildRedirectURI(
        string $base_redirect_uri,
        ?string $state_value,
        string $error_code
    ): UriInterface {
        $url_parts = parse_url($base_redirect_uri);
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $query);
        } else {
            $query = [];
        }
        if ($state_value !== null) {
            $query[AuthorizationEndpointGetController::STATE_PARAMETER] = $state_value;
        }
        $query[AuthorizationEndpointGetController::ERROR_PARAMETER] = $error_code;

        $url_parts['query'] = http_build_query($query);
        return Uri::fromParts($url_parts);
    }
}