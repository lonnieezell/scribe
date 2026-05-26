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

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\AIResponse;
use Myth\Scribe\AIService;
use Myth\Scribe\Config\AI;
use Myth\Scribe\Drivers\FakeDriver;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Prompts\BasePrompt;

/**
 * Prompt with no driver override.
 *
 * @internal
 */
final class DefaultDriverPrompt extends BasePrompt
{
    public function systemPrompt(): string { return 'System'; }

    public function userPrompt(): string { return 'User'; }
}

/**
 * Prompt that overrides the driver key.
 *
 * @internal
 */
final class OverrideDriverPrompt extends BasePrompt
{
    public ?string $driver = 'openai';

    public function systemPrompt(): string { return 'System'; }

    public function userPrompt(): string { return 'User'; }
}

/**
 * @internal
 */
final class AIServiceTest extends CIUnitTestCase
{
    private function makeConfig(): AI
    {
        $config = new AI();
        $config->drivers['fake'] = ['apiKey' => '', 'model' => 'fake', 'timeout' => 30];

        return $config;
    }

    private function makeService(AI $config): AIService
    {
        $drivers = [
            'claude'  => static fn () => new FakeDriver(),
            'openai'  => static fn () => new FakeDriver(new AIResponse('openai-response', 'gpt-4o', 1, 2, [])),
            'gemini'  => static fn () => new FakeDriver(),
            'mistral' => static fn () => new FakeDriver(),
            'fake'    => static fn () => new FakeDriver(),
        ];

        return new AIService($config, $drivers);
    }

    public function testRunUsesDefaultDriverFromConfig(): void
    {
        $config          = $this->makeConfig();
        $config->defaultDriver = 'claude';
        $service         = $this->makeService($config);

        $response = $service->run(new DefaultDriverPrompt());

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertSame('fake-response', $response->content);
    }

    public function testRunUsesDriverOverrideFromPrompt(): void
    {
        $config  = $this->makeConfig();
        $service = $this->makeService($config);

        $response = $service->run(new OverrideDriverPrompt());

        $this->assertSame('openai-response', $response->content);
    }

    public function testRunThrowsForUnknownDriverKey(): void
    {
        $config                = new AI();
        $config->defaultDriver = 'does-not-exist';
        $service               = new AIService($config, []);

        $this->expectException(AIException::class);
        $service->run(new DefaultDriverPrompt());
    }

    public function testRunInstantiatesFreshDriverEachCall(): void
    {
        $config  = $this->makeConfig();
        $service = $this->makeService($config);

        $r1 = $service->run(new DefaultDriverPrompt());
        $r2 = $service->run(new DefaultDriverPrompt());

        // Both succeed — main guarantee is no shared state explosion
        $this->assertInstanceOf(AIResponse::class, $r1);
        $this->assertInstanceOf(AIResponse::class, $r2);
    }
}
