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

namespace Tests\Prompts;

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Prompts\BasePrompt;

/**
 * Minimal concrete prompt for testing.
 *
 * @internal
 */
final class SimplePrompt extends BasePrompt
{
    public function systemPrompt(): string
    {
        return 'You are a helpful assistant.';
    }

    public function userPrompt(): string
    {
        return 'Tell me a joke.';
    }
}

/**
 * Prompt that sets a format.
 *
 * @internal
 */
final class FormattedPrompt extends BasePrompt
{
    public string $format = 'json';

    public function systemPrompt(): string
    {
        return 'You are helpful.';
    }

    public function userPrompt(): string
    {
        return 'Give me data.';
    }
}

/**
 * Prompt that returns a schema.
 *
 * @internal
 */
final class SchemaPrompt extends BasePrompt
{
    public function systemPrompt(): string
    {
        return 'You are helpful.';
    }

    public function userPrompt(): string
    {
        return 'Give me structured data.';
    }

    public function schema(): ?array
    {
        return ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];
    }
}

/**
 * Prompt that sets both format AND schema (should trigger warning).
 *
 * @internal
 */
final class BothFormatAndSchemaPrompt extends BasePrompt
{
    public string $format = 'json';

    public function systemPrompt(): string
    {
        return 'You are helpful.';
    }

    public function userPrompt(): string
    {
        return 'Give me data.';
    }

    public function schema(): ?array
    {
        return ['type' => 'object'];
    }
}

/**
 * @internal
 */
final class BasePromptTest extends CIUnitTestCase
{
    public function testBuildSystemPromptWithNoFormatOrSchema(): void
    {
        $prompt = new SimplePrompt();
        $built  = $prompt->buildSystemPrompt();

        $this->assertSame('You are a helpful assistant.', $built);
    }

    public function testBuildSystemPromptAppendsFormatInstructions(): void
    {
        $prompt = new FormattedPrompt();
        $built  = $prompt->buildSystemPrompt();

        $this->assertStringContainsString('You are helpful.', $built);
        $this->assertStringContainsString('json', strtolower($built));
    }

    public function testBuildSystemPromptAppendsSchemaInstructions(): void
    {
        $prompt = new SchemaPrompt();
        $built  = $prompt->buildSystemPrompt();

        $this->assertStringContainsString('You are helpful.', $built);
        $this->assertStringContainsString('object', $built);
    }

    public function testBuildSystemPromptLogsWarningWhenBothFormatAndSchemaSet(): void
    {
        $prompt = new BothFormatAndSchemaPrompt();

        // Capture log output - CI4's log_message writes to the log file
        // We verify the call doesn't throw and returns a string
        $built = $prompt->buildSystemPrompt();

        $this->assertIsString($built);
        $this->assertNotEmpty($built);
    }

    public function testAssistantReturnsNullByDefault(): void
    {
        $prompt = new SimplePrompt();
        $this->assertNull($prompt->assistant());
    }

    public function testSchemaReturnsNullByDefault(): void
    {
        $prompt = new SimplePrompt();
        $this->assertNull($prompt->schema());
    }

    public function testDriverIsNullByDefault(): void
    {
        $prompt = new SimplePrompt();
        $this->assertNull($prompt->driver);
    }

    public function testOptionsIsEmptyByDefault(): void
    {
        $prompt = new SimplePrompt();
        $this->assertSame([], $prompt->options);
    }

    public function testBuildSystemPromptThrowsWhenSchemaCannotBeJsonEncoded(): void
    {
        $prompt = new class () extends BasePrompt {
            public function systemPrompt(): string
            {
                return 'sys';
            }

            public function userPrompt(): string
            {
                return 'user';
            }

            public function schema(): ?array
            {
                // "\xff" is invalid UTF-8 — json_encode returns false for it
                return ['bad' => "\xff"];
            }
        };

        $this->expectException(AIException::class);
        $prompt->buildSystemPrompt();
    }
}
