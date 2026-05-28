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

namespace Tests\Support;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Shared helpers for driver unit tests.
 */
abstract class DriverTestCase extends CIUnitTestCase
{
    protected function makeResponse(int $status, mixed $body): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn(is_string($body) ? $body : json_encode($body));

        return $response;
    }

    protected function makeClient(ResponseInterface $response): CURLRequest
    {
        $client = $this->createMock(CURLRequest::class);
        $client->method('request')->willReturn($response);

        return $client;
    }
}
