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
use Myth\Scribe\Drivers\GeminiDriver;
use Myth\Scribe\Exceptions\AIAuthException;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Exceptions\AIRateLimitException;
use Tests\Support\DriverTestCase;

/**
 * @internal
 */
final class GeminiDriverTest extends DriverTestCase
{
    private array $defaultConfig = [
        'apiKey'  => 'test-key',
        'model'   => 'gemini-2.0-flash',
        'timeout' => 30,
    ];

    private function happyBody(string $content = 'Hello world', string $modelVersion = 'gemini-2.0-flash-001'): array
    {
        return [
            'candidates'    => [['content' => ['parts' => [['text' => $content]]]]],
            'modelVersion'  => $modelVersion,
            'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5],
        ];
    }

    public function testHappyPathReturnsMappedAIResponse(): void
    {
        $body   = $this->happyBody();
        $client = $this->makeClient($this->makeResponse(200, $body));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $response = $driver->complete('You are helpful.', 'Say hello.', null, null, []);

        $this->assertSame('Hello world', $response->content);
        $this->assertSame('gemini-2.0-flash-001', $response->model);
        $this->assertSame(10, $response->inputTokens);
        $this->assertSame(5, $response->outputTokens);
        $this->assertSame($body, $response->raw);
    }

    public function testSchemaModePassesGenerationConfig(): void
    {
        $schema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts) use ($schema): bool {
                    $gc = $opts['json']['generationConfig'] ?? null;
                    self::assertIsArray($gc);
                    self::assertSame($schema, $gc['responseSchema']);
                    self::assertSame('application/json', $gc['responseMimeType']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new GeminiDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, $schema, []);
    }

    public function testNoSchemaModeOmitsGenerationConfig(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    self::assertArrayNotHasKey('generationConfig', $opts['json']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new GeminiDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testAssistantPrefillBuildsMultiTurnContents(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    $contents = $opts['json']['contents'] ?? [];
                    self::assertCount(3, $contents);
                    self::assertSame('user', $contents[0]['role']);
                    self::assertSame('Hello', $contents[0]['parts'][0]['text']);
                    self::assertSame('model', $contents[1]['role']);
                    self::assertSame('Hi there', $contents[1]['parts'][0]['text']);
                    self::assertSame('user', $contents[2]['role']);
                    self::assertSame('Hello', $contents[2]['parts'][0]['text']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody('Continued')));

        $driver   = new GeminiDriver($this->defaultConfig, $client);
        $response = $driver->complete('sys', 'Hello', 'Hi there', null, []);

        $this->assertSame('Continued', $response->content);
    }

    public function testBaseUrlOverrideAppendsModelAndAction(): void
    {
        $config = array_merge($this->defaultConfig, ['baseUrl' => 'https://proxy.example.com/v1beta/models']);

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('https://proxy.example.com/v1beta/models/gemini-2.0-flash:generateContent'),
                $this->anything(),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new GeminiDriver($config, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testMaxTokensSentWhenPresentInConfig(): void
    {
        $config = array_merge($this->defaultConfig, ['maxTokens' => 1024]);

        $client = $this->createMock(CURLRequest::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    $gc = $opts['json']['generationConfig'] ?? null;
                    self::assertIsArray($gc);
                    self::assertSame(1024, $gc['maxOutputTokens']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new GeminiDriver($config, $client);
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
                    self::assertArrayNotHasKey('generationConfig', $opts['json']);

                    return true;
                }),
            )
            ->willReturn($this->makeResponse(200, $this->happyBody()));

        $driver = new GeminiDriver($this->defaultConfig, $client);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testModelVersionFallsBackToConfigModel(): void
    {
        $body = [
            'candidates'    => [['content' => ['parts' => [['text' => 'ok']]]]],
            'usageMetadata' => ['promptTokenCount' => 1, 'candidatesTokenCount' => 1],
        ];

        $client = $this->makeClient($this->makeResponse(200, $body));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $response = $driver->complete('sys', 'user', null, null, []);

        $this->assertSame('gemini-2.0-flash', $response->model);
    }

    public function testHttp401ThrowsAIAuthException(): void
    {
        $client = $this->makeClient($this->makeResponse(401, '{"error":"unauthorized"}'));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $this->expectException(AIAuthException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testHttp403ThrowsAIAuthException(): void
    {
        $client = $this->makeClient($this->makeResponse(403, '{"error":"forbidden"}'));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $this->expectException(AIAuthException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testHttp429ThrowsAIRateLimitException(): void
    {
        $client = $this->makeClient($this->makeResponse(429, '{"error":"rate_limit"}'));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $this->expectException(AIRateLimitException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testOtherNon2xxThrowsAIException(): void
    {
        $client = $this->makeClient($this->makeResponse(500, '{"error":"server_error"}'));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testNetworkFailureThrowsAIException(): void
    {
        $client = $this->createMock(CURLRequest::class);
        $client->method('request')->willThrowException(HTTPException::forCurlError('6', 'Could not resolve host'));

        $driver = new GeminiDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }

    public function testMalformedResponseBodyThrowsAIException(): void
    {
        $client = $this->makeClient($this->makeResponse(200, '{"unexpected":"shape"}'));
        $driver = new GeminiDriver($this->defaultConfig, $client);

        $this->expectException(AIException::class);
        $driver->complete('sys', 'user', null, null, []);
    }
}
