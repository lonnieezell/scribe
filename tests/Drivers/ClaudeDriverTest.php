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

namespace Tests\Drivers;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\Drivers\ClaudeDriver;
use Myth\Scribe\Exceptions\AIAuthException;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Exceptions\AIRateLimitException;

/**
 * @internal
 */
final class ClaudeDriverTest extends CIUnitTestCase
{
    private array $defaultConfig = [
        'apiKey'    => 'test-key',
        'model'     => 'claude-haiku-4-5',
        'timeout'   => 30,
        'maxTokens' => 4096,
    ];

    private function makeResponse(int $status, mixed $body): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn(is_string($body) ? $body : json_encode($body));

        return $response;
    }

    private function makeClient(ResponseInterface $response): CURLRequest
    {
        $client = $this->createMock(CURLRequest::class);
        $client->method('request')->willReturn($response);

        return $client;
    }

    public function testHappyPathReturnsMappedAIResponse(): void
    {
        $body = [
            'content' => [['type' => 'text', 'text' => 'Hello world']],
            'model'   => 'claude-haiku-4-5',
            'usage'   => ['input_tokens' => 10, 'output_tokens' => 5],
        ];

        $client = $this->makeClient($this->makeResponse(200, $body));
        $driver = new ClaudeDriver($this->defaultConfig, $client);

        $response = $driver->complete('You are helpful.', 'Say hello.', null, null, []);

        $this->assertSame('Hello world', $response->content);
        $this->assertSame('claude-haiku-4-5', $response->model);
        $this->assertSame(10, $response->inputTokens);
        $this->assertSame(5, $response->outputTokens);
        $this->assertSame($body, $response->raw);
    }

    public function testAssistantPrefillAppendsAssistantTurn(): void
    {
        $body = [
            'content' => [['type' => 'text', 'text' => 'Continued']],
            'model'   => 'claude-haiku-4-5',
            'usage'   => ['input_tokens' => 5, 'output_tokens' => 3],
        ];

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    $messages = $opts['json']['messages'] ?? [];

                    return count($messages) === 2
                        && $messages[0] === ['role' => 'user', 'content' => 'Hello']
                        && $messages[1] === ['role' => 'assistant', 'content' => 'Hi there'];
                }),
            )
            ->willReturn($this->makeResponse(200, $body));

        $driver   = new ClaudeDriver($this->defaultConfig, $client);
        $response = $driver->complete('sys', 'Hello', 'Hi there', null, []);

        $this->assertSame('Continued', $response->content);
    }

    public function testOptionsOverrideDriverKeys(): void
    {
        $body = [
            'content' => [['type' => 'text', 'text' => 'ok']],
            'model'   => 'claude-opus-4-7',
            'usage'   => ['input_tokens' => 1, 'output_tokens' => 1],
        ];

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    return ($opts['json']['model'] ?? '') === 'claude-opus-4-7';
                }),
            )
            ->willReturn($this->makeResponse(200, $body));

        $driver = new ClaudeDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, null, ['model' => 'claude-opus-4-7']);

        // assertion is on the mock expectation above
        $this->addToAssertionCount(1);
    }

    public function testBaseUrlOverrideIsUsed(): void
    {
        $config = array_merge($this->defaultConfig, ['baseUrl' => 'https://proxy.example.com/v1/messages']);

        $body = [
            'content' => [['type' => 'text', 'text' => 'ok']],
            'model'   => 'claude-haiku-4-5',
            'usage'   => ['input_tokens' => 1, 'output_tokens' => 1],
        ];

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', 'https://proxy.example.com/v1/messages', $this->anything())
            ->willReturn($this->makeResponse(200, $body));

        $driver = new ClaudeDriver($config, $client);
        $driver->complete('sys', 'user', null, null, []);

        $this->addToAssertionCount(1);
    }

    public function testHttp401ThrowsAIAuthException(): void
    {
        $client = $this->makeClient($this->makeResponse(401, '{"error":"unauthorized"}'));
        $driver = new ClaudeDriver($this->defaultConfig, $client);

        $this->expectException(AIAuthException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testHttp429ThrowsAIRateLimitException(): void
    {
        $client = $this->makeClient($this->makeResponse(429, '{"error":"rate_limit"}'));
        $driver = new ClaudeDriver($this->defaultConfig, $client);

        $this->expectException(AIRateLimitException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testOtherNon2xxThrowsAIException(): void
    {
        $client = $this->makeClient($this->makeResponse(500, '{"error":"server_error"}'));
        $driver = new ClaudeDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testNetworkFailureThrowsAIException(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->method('request')->willThrowException(HTTPException::forCurlError('6', 'Could not resolve host'));

        $driver = new ClaudeDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testMalformedResponseBodyThrowsAIException(): void
    {
        $client = $this->makeClient($this->makeResponse(200, '{"unexpected":"shape"}'));
        $driver = new ClaudeDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }
}
