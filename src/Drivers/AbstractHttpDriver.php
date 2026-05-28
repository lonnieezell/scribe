<?php

declare(strict_types=1);

/**
 * This file is part of Myth/Scribe.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Myth\Scribe\Drivers;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use Myth\Scribe\Exceptions\AIAuthException;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Exceptions\AIRateLimitException;

/**
 * Shared HTTP request/response/error handling for provider drivers.
 */
abstract class AbstractHttpDriver implements AIDriver
{
    public function __construct(
        protected readonly array $config,
        protected readonly CURLRequest $client,
    ) {
    }

    abstract protected function providerName(): string;

    /**
     * HTTP status codes that should throw AIAuthException.
     *
     * @return list<int>
     */
    protected function authStatusCodes(): array
    {
        return [401];
    }

    /**
     * POST to $url, handle error status codes, decode JSON, and return the body array.
     *
     * @param array<string, mixed> $requestOptions CURLRequest options (headers, json, timeout, http_errors)
     *
     * @return array<string, mixed>
     */
    protected function sendRequest(string $url, array $requestOptions): array
    {
        try {
            $response = $this->client->request('POST', $url, $requestOptions);
        } catch (HTTPException $e) {
            throw new AIException(
                'Network error communicating with ' . $this->providerName() . ' API: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        $status = $response->getStatusCode();

        if (in_array($status, $this->authStatusCodes(), true)) {
            throw new AIAuthException($this->providerName() . " API authentication failed (HTTP {$status}).");
        }

        if ($status === 429) {
            throw new AIRateLimitException($this->providerName() . ' API rate limit exceeded (HTTP 429).');
        }

        if ($status >= 400) {
            throw new AIException($this->providerName() . " API returned HTTP {$status}.");
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data)) {
            throw new AIException('Malformed response from ' . $this->providerName() . ' API.');
        }

        return $data;
    }
}
