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
use Myth\Scribe\Drivers\OpenAIDriver;
use Myth\Scribe\Exceptions\AIAuthException;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Exceptions\AIRateLimitException;
use Tests\Support\DriverTestCase;

/**
 * @internal
 */
final class OpenAIDriverTest extends DriverTestCase
{
    private array $defaultConfig = [
        'apiKey'  => 'test-key',
        'model'   => 'gpt-4o-mini',
        'timeout' => 30,
    ];

    private function happyBody(string $content = 'Hello world', string $model = 'gpt-4o-mini'): array
    {
        return [
            'choices' => [['message' => ['content' => $content]]],
            'model'   => $model,
            'usage'   => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ];
    }

    public function testHappyPathReturnsMappedAIResponse(): void
    {
        $body   = $this->happyBody();
        $client = $this->makeClient($this->makeResponse(200, $body));
        $driver = new OpenAIDriver($this->defaultConfig, $client);

        $response = $driver->complete('You are helpful.', 'Say hello.', null, null, []);

        $this->assertSame('Hello world', $response->content);
        $this->assertSame('gpt-4o-mini', $response->model);
        $this->assertSame(10, $response->inputTokens);
        $this->assertSame(5, $response->outputTokens);
        $this->assertSame($body, $response->raw);
    }

    public function testSchemaModePassesResponseFormat(): void
    {
        $schema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts) use ($schema): bool {
                    $rf = $opts['json']['response_format'] ?? null;
                    self::assertIsArray($rf);
                    self::assertSame('json_schema', $rf['type']);
                    self::assertSame('response', $rf['json_schema']['name']);
                    self::assertSame($schema, $rf['json_schema']['schema']);
                    self::assertTrue($rf['json_schema']['strict']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new OpenAIDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, $schema, []);
    }

    public function testNoSchemaModeOmitsResponseFormat(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    self::assertArrayNotHasKey('response_format', $opts['json']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new OpenAIDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testAssistantPrefillAppendsAssistantTurn(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    $messages = $opts['json']['messages'] ?? [];
                    self::assertCount(3, $messages);
                    self::assertSame('system', $messages[0]['role']);
                    self::assertSame(['role' => 'user', 'content' => 'Hello'], $messages[1]);
                    self::assertSame(['role' => 'assistant', 'content' => 'Hi there'], $messages[2]);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody('Continued')));

        $driver   = new OpenAIDriver($this->defaultConfig, $client);
        $response = $driver->complete('sys', 'Hello', 'Hi there', null, []);

        $this->assertSame('Continued', $response->content);
    }

    public function testBaseUrlOverrideIsUsed(): void
    {
        $config = array_merge($this->defaultConfig, ['baseUrl' => 'https://proxy.example.com/v1/chat/completions']);

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', 'https://proxy.example.com/v1/chat/completions', $this->anything())
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new OpenAIDriver($config, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testMaxTokensSentWhenPresentInConfig(): void
    {
        $config = array_merge($this->defaultConfig, ['maxTokens' => 2048]);

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    self::assertSame(2048, $opts['json']['max_tokens'] ?? null);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new OpenAIDriver($config, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testMaxTokensOmittedWhenAbsentFromConfig(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    self::assertArrayNotHasKey('max_tokens', $opts['json']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new OpenAIDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testHttp401ThrowsAIAuthException(): void
    {
        $client = $this->makeClient($this->makeResponse(401, '{"error":"unauthorized"}'));
        $driver = new OpenAIDriver($this->defaultConfig, $client);

        $this->expectException(AIAuthException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testHttp429ThrowsAIRateLimitException(): void
    {
        $client = $this->makeClient($this->makeResponse(429, '{"error":"rate_limit"}'));
        $driver = new OpenAIDriver($this->defaultConfig, $client);

        $this->expectException(AIRateLimitException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testOtherNon2xxThrowsAIException(): void
    {
        $client = $this->makeClient($this->makeResponse(500, '{"error":"server_error"}'));
        $driver = new OpenAIDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testNetworkFailureThrowsAIException(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->method('request')->willThrowException(HTTPException::forCurlError('6', 'Could not resolve host'));

        $driver = new OpenAIDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testMalformedResponseBodyThrowsAIException(): void
    {
        $client = $this->makeClient($this->makeResponse(200, '{"unexpected":"shape"}'));
        $driver = new OpenAIDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }
}
